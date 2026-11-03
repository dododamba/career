<?php

namespace App\Tests\Organisation;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Service;
use App\Entity\Structure;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OrganigrammeTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $userA;
    private Structure $structureA;
    private Structure $structureB;
    private Service $serviceA;
    private Employe $employeA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Create Tenant A & B
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Ministère Alpha ' . uniqid());
        $this->tenantA->setCode('MIN-A-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Ministère Beta ' . uniqid());
        $this->tenantB->setCode('MIN-B-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Create User A
        $this->userA = new User();
        $this->userA->setEmail('admin_org_' . uniqid() . '@min-alpha.gov');
        $this->userA->setRoles(['ROLE_ADMIN']);
        $this->userA->setPassword($this->hasher->hashPassword($this->userA, 'Password123!'));
        $this->userA->setInstitution($this->tenantA);
        $this->userA->setIsVerified(true);
        $this->em->persist($this->userA);

        // 3. Create Employe A
        $this->employeA = new Employe();
        $this->employeA->setNom('Directeur');
        $this->employeA->setPrenom('Général');
        $this->employeA->setUser($this->userA);
        $this->employeA->setInstitution($this->tenantA);
        $this->employeA->setMatricule('DIR-' . uniqid());
        $this->employeA->setSalaire(2000.0);
        $this->employeA->setDateEmbauche(new \DateTime('2025-01-01'));
        $this->employeA->setActif(true);
        $this->em->persist($this->employeA);

        // Assign responsable to institution
        $this->tenantA->setResponsable($this->employeA);

        // 4. Create Structure A (under Tenant A)
        $this->structureA = new Structure();
        $this->structureA->setNom('Direction Informatique Alpha ' . uniqid());
        $this->structureA->setCode('DSI-A');
        $this->structureA->setType(Structure::TYPE_DIRECTION);
        $this->structureA->setInstitution($this->tenantA);
        $this->structureA->setResponsable($this->employeA);
        $this->em->persist($this->structureA);

        // 5. Create Service A (under Structure A)
        $this->serviceA = new Service();
        $this->serviceA->setNom('Service Réseaux Alpha ' . uniqid());
        $this->serviceA->setCode('SRA');
        $this->serviceA->setInstitution($this->tenantA);
        $this->structureA->addService($this->serviceA);
        $this->em->persist($this->serviceA);

        // 6. Create Structure B (under Tenant B)
        $this->structureB = new Structure();
        $this->structureB->setNom('Secret Direction Beta ' . uniqid());
        $this->structureB->setCode('SDB');
        $this->structureB->setType(Structure::TYPE_DIRECTION);
        $this->structureB->setInstitution($this->tenantB);
        $this->em->persist($this->structureB);

        $this->em->flush();
        $this->em->clear();
    }

    public function testOrganigrammePageRendersSuccessfully(): void
    {
        $this->client->loginUser($this->userA);
        $this->client->request('GET', '/admin/organigramme');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Organigramme Hiérarchique', $content);
        $this->assertStringContainsString($this->structureA->getNom(), $content);
        $this->assertStringContainsString($this->serviceA->getNom(), $content);
        // Tenant B's structure must NOT appear
        $this->assertStringNotContainsString($this->structureB->getNom(), $content);
    }

    public function testOrganigrammeDataEndpointReturnsJsonTree(): void
    {
        $this->client->loginUser($this->userA);
        $this->client->request('GET', '/admin/organigramme/data');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertEquals($this->tenantA->getNom(), $data['name']);
        $this->assertArrayHasKey('children', $data);
        $this->assertNotEmpty($data['children']);

        // Check structure node
        $structNode = $data['children'][0];
        $this->assertEquals($this->structureA->getNom(), $structNode['name']);
        $this->assertArrayHasKey('services', $structNode);
        $this->assertNotEmpty($structNode['services']);
        $this->assertEquals($this->serviceA->getNom(), $structNode['services'][0]['name']);
    }

    public function testInstitutionShow360ViewRendersTabs(): void
    {
        $this->client->loginUser($this->userA);
        $this->client->request('GET', '/admin/institution/' . $this->tenantA->getId());

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Vue 360°', $content);
        $this->assertStringContainsString('Structures', $content);
        $this->assertStringContainsString('Services', $content);
        $this->assertStringContainsString('Historique organisationnel', $content);
        $this->assertStringContainsString($this->structureA->getNom(), $content);
        $this->assertStringContainsString($this->serviceA->getNom(), $content);
    }
}
