<?php

namespace App\Tests\Security;

use App\Entity\Institution;
use App\Entity\SecurityAuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MfaTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);

        $this->institution = new Institution();
        $this->institution->setNom('MFA Test Org ' . uniqid());
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
        $user->setFirstname('Mfa');
        $user->setLastname('Tester');
        $user->setIsVerified(true);
        $user->setInstitution($this->institution);
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testEnableMfaFlow(): void
    {
        $email = 'mfa_enable_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'Password123!');

        $this->client->loginUser($user);

        // 1. Visit setup page
        $crawler = $this->client->request('GET', '/account/2fa/setup');
        $this->assertResponseIsSuccessful();

        $session = $this->client->getRequest()->getSession();
        $secret = $session->get('2fa_setup_secret');
        $this->assertNotEmpty($secret);

        // 2. Generate valid TOTP code
        $totp = TOTP::createFromSecret($secret);
        $validCode = $totp->now();

        // 3. Submit confirmation code
        $this->client->request('POST', '/account/2fa/enable', [
            'code' => $validCode,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.bg-light'); // Backup codes table

        // 4. Verify user in database
        $this->em->clear();
        $updatedUser = $this->em->find(User::class, $user->getId());
        $this->assertTrue($updatedUser->isTotpEnabled());
        $this->assertSame($secret, $updatedUser->getTotpSecret());
        $this->assertCount(8, $updatedUser->getBackupCodes());

        // 5. Verify audit log
        $auditLogs = $this->em->getRepository(SecurityAuditLog::class)->findBy([
            'userIdentifier' => $email,
            'event' => SecurityAuditLog::EVENT_MFA_ENABLED,
        ]);
        $this->assertNotEmpty($auditLogs);
    }

    public function testLoginWithMfaChallengeSuccess(): void
    {
        $email = 'mfa_login_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'Password123!');

        $totp = TOTP::generate();
        $secret = $totp->getSecret();

        $user->setTotpSecret($secret);
        $user->setIsTotpEnabled(true);
        $this->em->flush();

        // 1. First step: submit primary credentials
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $this->client->submit($form);

        // 2FA challenge is triggered
        $this->assertResponseRedirects('http://localhost/2fa');
        $this->client->followRedirect();

        // 2. Second step: submit TOTP code
        $validCode = TOTP::createFromSecret($secret)->now();
        $this->client->request('POST', '/2fa_check', [
            '_auth_code' => $validCode,
        ]);

        $this->assertResponseRedirects('/dashboard');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithMfaChallengeFailsWithWrongCode(): void
    {
        $email = 'mfa_fail_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'Password123!');

        $totp = TOTP::generate();
        $user->setTotpSecret($totp->getSecret());
        $user->setIsTotpEnabled(true);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('http://localhost/2fa');
        $this->client->followRedirect();

        // Submit wrong code
        $this->client->request('POST', '/2fa_check', [
            '_auth_code' => '000000',
        ]);

        // Redirects back to 2FA challenge
        $this->assertResponseRedirects('http://localhost/2fa');
    }

    public function testLoginWithBackupCode(): void
    {
        $email = 'mfa_backup_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'Password123!');

        $totp = TOTP::generate();
        $rawBackupCode = 'RECOVERY123';
        $user->setTotpSecret($totp->getSecret());
        $user->setIsTotpEnabled(true);
        $user->setBackupCodes([hash('sha256', $rawBackupCode)]);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('http://localhost/2fa');
        $this->client->followRedirect();

        // Submit backup code
        $this->client->request('POST', '/2fa_check', [
            '_auth_code' => $rawBackupCode,
        ]);

        $this->assertResponseRedirects('/dashboard');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify backup code is now consumed and removed
        $this->em->clear();
        $updatedUser = $this->em->find(User::class, $user->getId());
        $this->assertFalse($updatedUser->isBackupCode($rawBackupCode));
        $this->assertEmpty($updatedUser->getBackupCodes());
    }

    public function testDisableMfa(): void
    {
        $email = 'mfa_disable_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'Password123!');

        $totp = TOTP::generate();
        $user->setTotpSecret($totp->getSecret());
        $user->setIsTotpEnabled(true);
        $user->setBackupCodes([hash('sha256', 'BACKUP')]);
        $this->em->flush();

        $this->client->loginUser($user);

        // Fetch page and extract CSRF token for disable_2fa
        $crawler = $this->client->request('GET', '/account/security');
        $this->assertResponseIsSuccessful();
        $csrfToken = $crawler->filter('form[action$="/account/2fa/disable"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/account/2fa/disable', [
            '_token' => $csrfToken,
            'current_password' => 'Password123!',
        ]);

        $this->assertResponseRedirects('/account/security');

        $this->em->clear();
        $updatedUser = $this->em->find(User::class, $user->getId());
        $this->assertFalse($updatedUser->isTotpEnabled());
        $this->assertNull($updatedUser->getTotpSecret());
        $this->assertEmpty($updatedUser->getBackupCodes());

        // Verify audit log
        $auditLogs = $this->em->getRepository(SecurityAuditLog::class)->findBy([
            'userIdentifier' => $email,
            'event' => SecurityAuditLog::EVENT_MFA_DISABLED,
        ]);
        $this->assertNotEmpty($auditLogs);
    }
}
