<?php

namespace App\Tests\Controller;

use App\Entity\Institution;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeRoutesTest extends WebTestCase
{
    #[DataProvider('publicRoutesProvider')]
    public function testPublicRoutesReturnSuccessfulResponse(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [200, 302], true),
            sprintf('Route "%s" returned HTTP status %d, expected 200 or 302.', $url, $statusCode)
        );
    }

    public static function publicRoutesProvider(): array
    {
        return [
            'login' => ['/login'],
            'register_as_partner' => ['/register-as-partner'],
            'home' => ['/'],
        ];
    }

    public function testAuthenticatedDashboard(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');

        $institution = new Institution();
        $institution->setNom('Smoke Test Institution');
        $institution->setCreatedAt(new \DateTimeImmutable());
        $em->persist($institution);

        $user = new User();
        $user->setEmail('smoke_test_' . uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setFirstname('Smoke');
        $user->setLastname('Admin');
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setInstitution($institution);
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        $adminRoutes = [
            '/dashboard',
            '/admin/employe',
            '/admin/pointage/',
            '/admin/pointage/dashboard',
            '/admin/conges/',
            '/admin/visite/',
            '/admin/statistique/',
            '/admin/service',
            '/admin/institution',
            '/admin/type-institution',
            '/admin/user',
            '/log',
        ];

        foreach ($adminRoutes as $route) {
            $client->request('GET', $route);
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertSame(
                200,
                $statusCode,
                sprintf('Route "%s" returned HTTP status %d, expected 200.', $route, $statusCode)
            );
        }

        // Cleanup
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $userToRemove = $em->find(User::class, $user->getId());
        if ($userToRemove) {
            $em->remove($userToRemove);
        }
        $instToRemove = $em->find(Institution::class, $institution->getId());
        if ($instToRemove) {
            $em->remove($instToRemove);
        }
        $em->flush();
    }

    public function testPointageDashboardWithData(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');

        $institution = new Institution();
        $institution->setNom('Pointage Smoke Institution ' . uniqid());
        $institution->setCreatedAt(new \DateTimeImmutable());
        $em->persist($institution);

        $user = new User();
        $user->setEmail('pointage_user_' . uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setFirstname('Pointage');
        $user->setLastname('Admin');
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setInstitution($institution);
        $em->persist($user);

        $employe = new \App\Entity\Employe();
        $employe->setNom('Dupont');
        $employe->setPrenom('Jean');
        $employe->setUser($user);
        $employe->setSalaire(3500.0);
        $employe->setActif(true);
        $em->persist($employe);

        $pointage = new \App\Entity\Pointage();
        $pointage->setEmploye($employe);
        $pointage->setInstitution($institution);
        $pointage->setStatus('Présent');
        $pointage->setDate(new \DateTime());
        $pointage->setDateEntree(new \DateTime());
        $pointage->setHeureArrivee(new \DateTime('08:30:00'));
        $em->persist($pointage);

        $pointageAbsent = new \App\Entity\Pointage();
        $pointageAbsent->setEmploye($employe);
        $pointageAbsent->setInstitution($institution);
        $pointageAbsent->setStatus('Absent');
        $pointageAbsent->setDate(new \DateTime());
        $pointageAbsent->setDateEntree(new \DateTime());
        $em->persist($pointageAbsent);

        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/admin/pointage/dashboard');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Dupont', $client->getResponse()->getContent());

        // Cleanup
        $em->remove($pointageAbsent);
        $em->remove($pointage);
        $em->remove($employe);
        $em->remove($user);
        $em->remove($institution);
        $em->flush();
    }
}
