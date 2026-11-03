<?php

namespace App\Tests\MultiTenancy;

use App\Entity\Conges;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Pointage;
use App\Entity\Service;
use App\Entity\TypeConges;
use App\Entity\User;
use App\Entity\Visite;
use App\MultiTenancy\TenantContextInterface;
use App\MultiTenancy\TenantInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TenantIsolationTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $userA;
    private User $userB;
    private User $superAdmin;
    private Employe $employeA;
    private Employe $employeB;
    private Service $serviceA;
    private Service $serviceB;
    private Visite $visiteB;
    private Pointage $pointageB;
    private Conges $congesB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);

        // Disable filter during fixture creation
        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Create Tenant A
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Alpha ' . uniqid());
        $this->tenantA->setCode('TA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->tenantA->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($this->tenantA);

        // 2. Create Tenant B
        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Beta ' . uniqid());
        $this->tenantB->setCode('TB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->tenantB->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($this->tenantB);

        // 3. Create Users
        $this->userA = $this->createUser('admin_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->userB = $this->createUser('admin_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);
        $this->superAdmin = $this->createUser('super_' . uniqid() . '@platform.com', ['ROLE_SUPER_ADMIN'], null);

        // 4. Create Services
        $this->serviceA = new Service();
        $this->serviceA->setNom('Service Alpha ' . uniqid());
        $this->serviceA->setDescription('Service for Alpha');
        $this->serviceA->setInstitution($this->tenantA);
        $this->em->persist($this->serviceA);

        $this->serviceB = new Service();
        $this->serviceB->setNom('Service Beta ' . uniqid());
        $this->serviceB->setDescription('Service for Beta');
        $this->serviceB->setInstitution($this->tenantB);
        $this->em->persist($this->serviceB);

        // 5. Create Employees
        $this->employeA = new Employe();
        $this->employeA->setNom('Employee');
        $this->employeA->setPrenom('Alpha');
        $this->employeA->setEmail('emp.a.' . uniqid() . '@alpha.com');
        $this->employeA->setMatricule('MAT-A-' . uniqid());
        $this->employeA->setSalaire(1000.0);
        $this->employeA->setDateEmbauche(new \DateTime('2025-01-01'));
        $this->employeA->setUser($this->userA);
        $this->employeA->setInstitution($this->tenantA);
        $this->employeA->setService($this->serviceA);
        $this->em->persist($this->employeA);

        $this->employeB = new Employe();
        $this->employeB->setNom('Employee');
        $this->employeB->setPrenom('Beta');
        $this->employeB->setEmail('emp.b.' . uniqid() . '@beta.com');
        $this->employeB->setMatricule('MAT-B-' . uniqid());
        $this->employeB->setSalaire(2000.0);
        $this->employeB->setDateEmbauche(new \DateTime('2025-01-01'));
        $this->employeB->setUser($this->userB);
        $this->employeB->setInstitution($this->tenantB);
        $this->employeB->setService($this->serviceB);
        $this->em->persist($this->employeB);

        // 6. Create Operational Entities for Tenant B
        $this->visiteB = new Visite();
        $this->visiteB->setVisiteur('Visitor for Beta');
        $this->visiteB->setArrivee(new \DateTime('now'));
        $this->visiteB->setEmploye($this->employeB);
        $this->visiteB->setInstitution($this->tenantB);
        $this->em->persist($this->visiteB);

        $this->pointageB = new Pointage();
        $this->pointageB->setDate(new \DateTime('today'));
        $this->pointageB->setDateEntree(new \DateTime('now'));
        $this->pointageB->setEmploye($this->employeB);
        $this->pointageB->setInstitution($this->tenantB);
        $this->em->persist($this->pointageB);

        $typeConge = $this->em->getRepository(TypeConges::class)->findOneBy([]);
        if (!$typeConge) {
            $typeConge = new TypeConges();
            $typeConge->setNom('Annuel');
            $typeConge->setNombreJours(30);
            $this->em->persist($typeConge);
        }

        $this->congesB = new Conges();
        $this->congesB->setEmploye($this->employeB);
        $this->congesB->setInstitution($this->tenantB);
        $this->congesB->setTypeConge($typeConge);
        $this->congesB->setDateDebut(new \DateTime('+1 day'));
        $this->congesB->setDateFin(new \DateTime('+5 days'));
        $this->congesB->setDateDiff(5);
        $this->congesB->setStatut(0);
        $this->congesB->setCreatedAt(new \DateTime());
        $this->congesB->setUpdatedAt(new \DateTime());
        $this->em->persist($this->congesB);

        $this->em->flush();
    }

    private function createUser(string $email, array $roles, ?Institution $institution): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, 'Password123!'));
        $user->setRoles($roles);
        $user->setFirstname('User');
        $user->setLastname('Test');
        $user->setIsVerified(true);
        $user->setInstitution($institution);
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        return $user;
    }

    public function testCrossTenantEmployeeAccessIsDenied(): void
    {
        // Login as Admin A (Tenant A)
        $this->client->loginUser($this->userA);

        // Attempt to VIEW Employee B (Tenant B)
        $this->client->request('GET', '/admin/employe/' . $this->employeB->getId());
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant employee show must be 403 or 404');

        // Attempt to EDIT Employee B
        $this->client->request('GET', '/admin/employe/' . $this->employeB->getId() . '/edit');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant employee edit must be 403 or 404');

        // Attempt to DELETE Employee B
        $this->client->request('POST', '/admin/employe/' . $this->employeB->getId());
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant employee delete must be 403 or 404');

        // Attempt to GET Solde for Employee B
        $this->client->request('GET', '/admin/employe/' . $this->employeB->getId() . '/solde');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant employee solde must be 403 or 404');
    }

    public function testSameTenantEmployeeAccessIsAllowed(): void
    {
        $this->client->loginUser($this->userA);

        $this->client->request('GET', '/admin/employe/' . $this->employeA->getId());
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/employe/' . $this->employeA->getId() . '/solde');
        $this->assertResponseIsSuccessful();
    }

    public function testCrossTenantServiceApiIsDenied(): void
    {
        $this->client->loginUser($this->userA);

        // Requesting services of Tenant B
        $this->client->request('GET', '/api/services/' . $this->tenantB->getId());
        $this->assertSame(403, $this->client->getResponse()->getStatusCode(), 'Cross-tenant services API must return 403 Forbidden');

        // Requesting services of Tenant A
        $this->client->request('GET', '/api/services/' . $this->tenantA->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testCrossTenantServiceControllerAccessIsDenied(): void
    {
        $this->client->loginUser($this->userA);

        $this->client->request('GET', '/admin/service/' . $this->serviceB->getId());
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant service view must return 403 or 404');

        $this->client->request('GET', '/admin/service/' . $this->serviceA->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testCrossTenantVisiteAccessIsDenied(): void
    {
        $this->client->loginUser($this->userA);

        // Direct show of Tenant B visite
        $this->client->request('GET', '/admin/visite/' . $this->visiteB->getId());
        $this->assertTrue(
            $this->client->getResponse()->isRedirect('/admin/visite/') || in_array($this->client->getResponse()->getStatusCode(), [403, 404], true),
            'Cross-tenant visite show must redirect or return 403/404'
        );

        // List visites for Employee B (Tenant B)
        $this->client->request('GET', '/admin/visite/employe/' . $this->employeB->getId() . '/visites');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant employee visites list must return 403 or 404');
    }

    public function testCrossTenantPointageAccessIsDenied(): void
    {
        $this->client->loginUser($this->userA);

        // Show pointage of Tenant B
        $this->client->request('GET', '/admin/pointage/' . $this->pointageB->getId());
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant pointage view must return 403 or 404');

        // Pointages by Employee B
        $this->client->request('GET', '/admin/pointage/employe/' . $this->employeB->getId());
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant employee pointages view must return 403 or 404');
    }

    public function testCrossTenantCongesAccessIsDenied(): void
    {
        $this->client->loginUser($this->userA);

        // Direct edit of Conges B
        $this->client->request('GET', '/admin/conges/' . $this->congesB->getId() . '/edit');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant conges edit must return 403 or 404');

        // Approve Conges B
        $this->client->request('POST', '/admin/conges/' . $this->congesB->getId() . '/approve');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [403, 404], 'Cross-tenant conges approve must return 403 or 404');
    }

    public function testDoctrineFilterStrictIsolation(): void
    {
        /** @var TenantContextInterface $tenantContext */
        $tenantContext = $this->client->getContainer()->get(TenantContextInterface::class);

        // In Tenant A context:
        $tenantContext->executeInTenantContext($this->tenantA, function () {
            $employes = $this->em->getRepository(Employe::class)->findAll();
            $ids = array_map(fn($e) => $e->getId(), $employes);

            $this->assertContains($this->employeA->getId(), $ids, 'Tenant A context must include Employe A');
            $this->assertNotContains($this->employeB->getId(), $ids, 'Tenant A context must NOT include Employe B');

            $services = $this->em->getRepository(Service::class)->findAll();
            $serviceIds = array_map(fn($s) => $s->getId(), $services);
            $this->assertContains($this->serviceA->getId(), $serviceIds);
            $this->assertNotContains($this->serviceB->getId(), $serviceIds);
        });

        // In Tenant B context:
        $tenantContext->executeInTenantContext($this->tenantB, function () {
            $employes = $this->em->getRepository(Employe::class)->findAll();
            $ids = array_map(fn($e) => $e->getId(), $employes);

            $this->assertContains($this->employeB->getId(), $ids, 'Tenant B context must include Employe B');
            $this->assertNotContains($this->employeA->getId(), $ids, 'Tenant B context must NOT include Employe A');
        });
    }

    public function testSuperAdminCanAccessAcrossTenants(): void
    {
        $this->client->loginUser($this->superAdmin);

        // Super Admin can view Employee B
        $this->client->request('GET', '/admin/employe/' . $this->employeB->getId());
        $this->assertResponseIsSuccessful('Super Admin must be allowed to view Employee B');

        // Super Admin can view services API of Tenant B
        $this->client->request('GET', '/api/services/' . $this->tenantB->getId());
        $this->assertResponseIsSuccessful('Super Admin must be allowed to access any tenant services API');

        // Super Admin can view Service B
        $this->client->request('GET', '/admin/service/' . $this->serviceB->getId());
        $this->assertResponseIsSuccessful('Super Admin must be allowed to view Service B');
    }

    public function testSuspendedTenantBlocksAccess(): void
    {
        // Suspend Tenant A
        $this->tenantA->setStatus(TenantInterface::STATUS_SUSPENDED);
        $this->em->flush();

        $this->client->loginUser($this->userA);

        // Accessing dashboard should throw 403 TenantSuspendedException
        $this->client->request('GET', '/dashboard');
        $this->assertSame(403, $this->client->getResponse()->getStatusCode(), 'Suspended tenant users must receive 403 Forbidden');
    }
}
