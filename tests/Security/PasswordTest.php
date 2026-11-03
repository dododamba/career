<?php

namespace App\Tests\Security;

use App\Entity\Institution;
use App\Entity\PasswordResetToken;
use App\Entity\SecurityAuditLog;
use App\Entity\User;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private PasswordResetService $passwordResetService;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->passwordResetService = $this->client->getContainer()->get(PasswordResetService::class);

        $this->institution = new Institution();
        $this->institution->setNom('Password Test Org ' . uniqid());
        $this->institution->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($this->institution);
        $this->em->flush();
    }

    private function createUser(string $email, string $plainPassword): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_USER']);
        $user->setFirstname('Pass');
        $user->setLastname('Test');
        $user->setIsVerified(true);
        $user->setInstitution($this->institution);
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testChangePasswordSuccess(): void
    {
        $email = 'change_pass_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'InitialPassword123!');

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/account/change-password');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'change_password[currentPassword]' => 'InitialPassword123!',
            'change_password[newPassword][first]' => 'NewSecurePassword456!',
            'change_password[newPassword][second]' => 'NewSecurePassword456!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/account/security');
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('modifié avec succès', $crawler->text());

        // Verify audit log
        $auditLogs = $this->em->getRepository(SecurityAuditLog::class)->findBy([
            'userIdentifier' => $email,
            'event' => SecurityAuditLog::EVENT_PASSWORD_CHANGED,
        ]);
        $this->assertNotEmpty($auditLogs);

        // Verify that the new password works on login and old password fails
        $this->client->request('GET', '/logout');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'InitialPassword123!',
        ]);
        $this->client->submit($form);
        $this->assertResponseRedirects('/login'); // Old password rejected

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'NewSecurePassword456!',
        ]);
        $this->client->submit($form);
        $this->assertResponseRedirects('/dashboard'); // New password accepted
    }

    public function testChangePasswordFailsWithWrongCurrentPassword(): void
    {
        $email = 'change_fail_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'InitialPassword123!');

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/account/change-password');
        $form = $crawler->filter('form')->form([
            'change_password[currentPassword]' => 'WrongCurrentPassword!',
            'change_password[newPassword][first]' => 'NewSecurePassword456!',
            'change_password[newPassword][second]' => 'NewSecurePassword456!',
        ]);

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('form', 'Le mot de passe actuel est incorrect');
    }

    public function testChangePasswordFailsWithMismatchedConfirmation(): void
    {
        $email = 'change_mismatch_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'InitialPassword123!');

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/account/change-password');
        $form = $crawler->filter('form')->form([
            'change_password[currentPassword]' => 'InitialPassword123!',
            'change_password[newPassword][first]' => 'NewSecurePassword456!',
            'change_password[newPassword][second]' => 'CompletelyDifferentPass!',
        ]);

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('form', 'Les deux mots de passe doivent être identiques');
    }

    public function testForgotPasswordRequestForExistingUser(): void
    {
        $email = 'forgot_pass_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'InitialPassword123!');

        $ip = '10.0.1.' . rand(1, 254);
        $crawler = $this->client->request('GET', '/reset-password', server: ['REMOTE_ADDR' => $ip]);
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'reset_password_request[email]' => $email,
        ]);

        $this->client->submit($form, [], ['REMOTE_ADDR' => $ip]);
        $this->assertResponseRedirects('/reset-password/check-email');

        // Verify token in database
        $this->em->clear();
        $tokens = $this->em->getRepository(PasswordResetToken::class)->findBy(['user' => $user]);
        $this->assertNotEmpty($tokens);
        $token = $tokens[0];
        $this->assertFalse($token->isExpired());
        $this->assertFalse($token->isUsed());
    }

    public function testForgotPasswordGenericResponseForUnknownUser(): void
    {
        $ip = '10.0.2.' . rand(1, 254);
        $crawler = $this->client->request('GET', '/reset-password', server: ['REMOTE_ADDR' => $ip]);

        $form = $crawler->filter('form')->form([
            'reset_password_request[email]' => 'nonexistent_account_' . uniqid() . '@unknown.org',
        ]);

        $this->client->submit($form, [], ['REMOTE_ADDR' => $ip]);

        // Must still redirect to /reset-password/check-email (anti-enumeration)
        $this->assertResponseRedirects('/reset-password/check-email');
    }

    public function testResetPasswordWithValidToken(): void
    {
        $email = 'reset_token_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'OldPassword123!');

        $rawToken = $this->passwordResetService->createResetToken($user);

        $crawler = $this->client->request('GET', '/reset-password/reset/' . $rawToken);
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'reset_password[plainPassword][first]' => 'BrandNewPassword789!',
            'reset_password[plainPassword][second]' => 'BrandNewPassword789!',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/login');

        // Verify token is now marked as used
        $this->em->clear();
        $updatedUser = $this->em->find(User::class, $user->getId());
        $tokens = $this->em->getRepository(PasswordResetToken::class)->findBy(['user' => $updatedUser]);
        $this->assertTrue($tokens[0]->isUsed());

        // Verify new password works
        $crawler = $this->client->request('GET', '/login');
        $loginForm = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'BrandNewPassword789!',
        ]);
        $this->client->submit($loginForm);
        $this->assertResponseRedirects('/dashboard');
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $this->client->request('GET', '/reset-password/reset/invalid-fake-token-12345');
        $this->assertResponseRedirects('/reset-password');
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('invalide ou a expiré', $crawler->text());
    }
}
