<?php

namespace App\Tests\Organisation;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\OrganisationHistory;
use App\Entity\Structure;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Repository\OrganisationHistoryRepository;
use App\Repository\StructureRepository;
use App\Service\OrganisationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StructureTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $userA;
    private User $userB;
    private Structure $structureA;
    private Structure $structureB;
    private Employe $employeA;
    private Employe $employeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Create Tenant A
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Org Alpha ' . uniqid());
        $this->tenantA->setCode('TOA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        // 2. Create Tenant B
        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Org Beta ' . uniqid());
        $this->tenantB->setCode('TOB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 3. Create Users
        $this->userA = $this->createUser('admin_org_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->userB = $this->createUser('admin_org_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 4. Create Employes
        $this->employeA = new Employe();
        $this->employeA->setNom('Directeur');
        $this->employeA->setPrenom('Alpha');
        $this->employeA->setUser($this->userA);
        $this->employeA->setInstitution($this->tenantA);
        $this->employeA->setMatricule('EMP-A-' . uniqid());
        $this->employeA->setSalaire(1500.0);
        $this->employeA->setDateEmbauche(new \DateTime('2025-01-01'));
        $this->employeA->setActif(true);
        $this->em->persist($this->employeA);

        $this->employeB = new Employe();
        $this->employeB->setNom('Directeur');
        $this->employeB->setPrenom('Beta');
        $this->employeB->setUser($this->userB);
        $this->employeB->setInstitution($this->tenantB);
        $this->employeB->setMatricule('EMP-B-' . uniqid());
        $this->employeB->setSalaire(1800.0);
        $this->employeB->setDateEmbauche(new \DateTime('2025-01-01'));
        $this->employeB->setActif(true);
        $this->em->persist($this->employeB);

        // 5. Create Structures
        $this->structureA = new Structure();
        $this->structureA->setNom('Direction Générale Alpha ' . uniqid());
        $this->structureA->setCode('DGA');
        $this->structureA->setType(Structure::TYPE_DIRECTION_GENERALE);
        $this->structureA->setInstitution($this->tenantA);
        $this->structureA->setResponsable($this->employeA);
        $this->em->persist($this->structureA);

        $this->structureB = new Structure();
        $this->structureB->setNom('Direction Générale Beta ' . uniqid());
        $this->structureB->setCode('DGB');
        $this->structureB->setType(Structure::TYPE_DIRECTION_GENERALE);
        $this->structureB->setInstitution($this->tenantB);
        $this->structureB->setResponsable($this->employeB);
        $this->em->persist($this->structureB);

        $this->em->flush();
    }

    private function createUser(string $email, array $roles, ?Institution $institution): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, 'Password123!'));
        $user->setInstitution($institution);
        $user->setIsVerified(true);
        $this->em->persist($user);
        return $user;
    }

    public function testStructureIndexIsIsolatedBetweenTenants(): void
    {
        $this->client->loginUser($this->userA);
        $this->client->request('GET', '/admin/structure');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString($this->structureA->getNom(), $content);
        $this->assertStringNotContainsString($this->structureB->getNom(), $content);
    }

    public function testCrossTenantStructureAccessIsDenied(): void
    {
        $this->client->loginUser($this->userA);
        $this->client->request('GET', '/admin/structure/' . $this->structureB->getId());

        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($statusCode, [403, 404]), 'Accessing cross-tenant structure must return 403 or 404. Got: ' . $statusCode);
    }

    public function testCrossTenantStructureEditIsDenied(): void
    {
        $this->client->loginUser($this->userA);
        $this->client->request('GET', '/admin/structure/' . $this->structureB->getId() . '/edit');

        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($statusCode, [403, 404]), 'Editing cross-tenant structure must return 403 or 404. Got: ' . $statusCode);
    }

    public function testCrossTenantStructureDeleteIsDenied(): void
    {
        $this->client->loginUser($this->userA);
        $this->client->request('POST', '/admin/structure/' . $this->structureB->getId(), [
            '_token' => 'invalid_or_valid',
        ]);

        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($statusCode, [403, 404]), 'Deleting cross-tenant structure must return 403 or 404. Got: ' . $statusCode);
    }

    public function testHierarchyParentChildRelationship(): void
    {
        $subStructure = new Structure();
        $subStructure->setNom('Direction Adjointe ' . uniqid());
        $subStructure->setCode('DA');
        $subStructure->setType(Structure::TYPE_DIRECTION);
        $subStructure->setInstitution($this->tenantA);
        $subStructure->setParent($this->structureA);

        $this->em->persist($subStructure);
        $this->em->flush();

        $this->assertEquals($this->structureA->getId(), $subStructure->getParent()->getId());
        $this->assertTrue($this->structureA->getChildren()->contains($subStructure));
    }

    public function testCycleDetectionPreventsInvalidHierarchy(): void
    {
        $service = $this->client->getContainer()->get(OrganisationService::class);

        // Création d'une structure A et d'une sous-structure B
        $struct1 = new Structure();
        $struct1->setNom('Direction 1');
        $struct1->setInstitution($this->tenantA);
        $this->em->persist($struct1);

        $struct2 = new Structure();
        $struct2->setNom('Sous-direction 2');
        $struct2->setInstitution($this->tenantA);
        $struct2->setParent($struct1);
        $this->em->persist($struct2);

        $this->em->flush();

        // Tentative 1: se définir comme son propre parent
        $this->expectException(\InvalidArgumentException::class);
        $service->validateParentStructure($struct1, $struct1);
    }

    public function testIndirectCycleDetectionThrowsException(): void
    {
        $service = $this->client->getContainer()->get(OrganisationService::class);

        // A -> B -> C
        $structA = new Structure();
        $structA->setNom('A');
        $structA->setInstitution($this->tenantA);
        $this->em->persist($structA);

        $structB = new Structure();
        $structB->setNom('B');
        $structB->setInstitution($this->tenantA);
        $structB->setParent($structA);
        $this->em->persist($structB);

        $structC = new Structure();
        $structC->setNom('C');
        $structC->setInstitution($this->tenantA);
        $structC->setParent($structB);
        $this->em->persist($structC);

        $this->em->flush();

        // Tentative de définir C comme parent de A (A -> B -> C -> A = cycle)
        $this->expectException(\InvalidArgumentException::class);
        $service->validateParentStructure($structA, $structC);
    }

    public function testCrossTenantParentAssignmentIsRejected(): void
    {
        $service = $this->client->getContainer()->get(OrganisationService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->validateParentStructure($this->structureA, $this->structureB);
    }

    public function testCrossTenantResponsableAssignmentIsRejected(): void
    {
        $service = $this->client->getContainer()->get(OrganisationService::class);

        // Responsable de Tenant B assigné à Tenant A
        $this->expectException(\InvalidArgumentException::class);
        $service->validateResponsable($this->tenantA, $this->employeB);
    }

    public function testOrganisationHistoryIsRecorded(): void
    {
        $service = $this->client->getContainer()->get(OrganisationService::class);
        $historyRepo = $this->client->getContainer()->get(OrganisationHistoryRepository::class);

        $history = $service->logChange(
            $this->tenantA,
            OrganisationHistory::ENTITY_STRUCTURE,
            $this->structureA->getId(),
            $this->structureA->getNom(),
            OrganisationHistory::ACTION_CREATED,
            ['code' => 'DGA'],
            $this->userA
        );

        $this->assertNotNull($history->getId());
        $this->assertEquals($this->tenantA->getId(), $history->getInstitution()->getId());
        $this->assertEquals(OrganisationHistory::ACTION_CREATED, $history->getAction());

        $logs = $historyRepo->findByInstitution($this->tenantA);
        $this->assertNotEmpty($logs);
        $this->assertEquals($this->structureA->getNom(), $logs[0]->getEntityName());
    }
}
