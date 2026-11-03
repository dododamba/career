<?php

namespace App\Tests\Security;

use App\Entity\Institution;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationTest extends WebTestCase
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
        $this->institution->setNom('Auth Test Org ' . uniqid());
        $this->institution->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($this->institution);
        $this->em->flush();
    }

    private function createUser(string $email, string $plainPassword, bool $verified = true, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
        $user->setRoles($roles);
        $user->setFirstname('Test');
        $user->setLastname('Auth');
        $user->setIsVerified($verified);
        $user->setInstitution($this->institution);
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testLoginSuccess(): void
    {
        $email = 'auth_success_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'SecretPassword123!', true);

        $crawler = $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'SecretPassword123!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/dashboard');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify lastLoginAt was updated
        $this->em->clear();
        $updatedUser = $this->em->find(User::class, $user->getId());
        $this->assertNotNull($updatedUser->getLastLoginAt());
    }

    public function testLoginFailureWithInvalidCredentials(): void
    {
        $email = 'auth_fail_' . uniqid() . '@example.com';
        $this->createUser($email, 'SecretPassword123!', true);

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'WrongPassword!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testLoginFailureWithUnverifiedAccount(): void
    {
        $email = 'unverified_' . uniqid() . '@example.com';
        $this->createUser($email, 'SecretPassword123!', false); // not verified!

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'SecretPassword123!',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
        $this->assertStringContainsString('inactif', $crawler->filter('.alert-danger')->text());
    }

    public function testLogout(): void
    {
        $email = 'logout_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'SecretPassword123!', true);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/logout');
        $this->assertResponseRedirects('/login');
    }

    public function testOpenRedirectIsPrevented(): void
    {
        $email = 'open_redirect_' . uniqid() . '@example.com';
        $this->createUser($email, 'SecretPassword123!', true);

        // Attempting to set an external target path in session
        $crawler = $this->client->request('GET', '/login');
        $this->client->getRequest()->getSession()->set('_security.main.target_path', 'https://malicious-site.example.com/steal-creds');
        $this->client->getRequest()->getSession()->save();

        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'SecretPassword123!',
        ]);

        $this->client->submit($form);

        // Must redirect to internal dashboard, NOT to the external malicious URL
        $this->assertResponseRedirects('/dashboard');
    }
}
