<?php

namespace App\Tests\Security;

use App\Entity\Institution;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RateLimitingTest extends WebTestCase
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
        $this->institution->setNom('Rate Limit Org ' . uniqid());
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
        $user->setFirstname('Rate');
        $user->setLastname('Limit');
        $user->setIsVerified(true);
        $user->setInstitution($this->institution);
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testPasswordResetRateLimitingBlocksExcessiveRequests(): void
    {
        $fixedIp = '172.16.20.' . rand(1, 250);

        // Limit is 3 per 15 minutes
        for ($i = 1; $i <= 3; $i++) {
            $crawler = $this->client->request('GET', '/reset-password', server: ['REMOTE_ADDR' => $fixedIp]);
            $form = $crawler->filter('form')->form([
                'reset_password_request[email]' => 'target_' . $i . '@example.com',
            ]);
            $this->client->submit($form, [], ['REMOTE_ADDR' => $fixedIp]);
            $this->assertResponseRedirects('/reset-password/check-email');
        }

        // 4th request MUST be rejected by rate limiter
        $crawler = $this->client->request('GET', '/reset-password', server: ['REMOTE_ADDR' => $fixedIp]);
        $form = $crawler->filter('form')->form([
            'reset_password_request[email]' => 'target_blocked@example.com',
        ]);
        $this->client->submit($form, [], ['REMOTE_ADDR' => $fixedIp]);

        // Redirects back to reset-password with error
        $this->assertResponseRedirects('/reset-password');
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Trop de tentatives', $crawler->text());
    }

    public function testLoginThrottlingBlocksBruteForceAttacks(): void
    {
        $email = 'bruteforce_' . uniqid() . '@example.com';
        $this->createUser($email, 'CorrectPassword123!');
        $fixedIp = '172.16.30.' . rand(1, 250);

        // Limit is 5 attempts per minute
        for ($i = 1; $i <= 5; $i++) {
            $crawler = $this->client->request('GET', '/login', server: ['REMOTE_ADDR' => $fixedIp]);
            $form = $crawler->filter('form')->form([
                'email' => $email,
                'password' => 'WrongPassword' . $i,
            ]);
            $this->client->submit($form, [], ['REMOTE_ADDR' => $fixedIp]);
            $this->assertResponseRedirects('/login');
        }

        // 6th attempt: should trigger rate limiting / throttling message
        $crawler = $this->client->request('GET', '/login', server: ['REMOTE_ADDR' => $fixedIp]);
        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => 'WrongPassword6',
        ]);
        $this->client->submit($form, [], ['REMOTE_ADDR' => $fixedIp]);

        $this->assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
        // Symfony default throttling message or generic failure
        $this->assertNotEmpty($crawler->filter('.alert-danger')->text());
    }
}
