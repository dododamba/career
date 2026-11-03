<?php

namespace App\Tests\Agent;

use App\Entity\Employe;
use App\Entity\Fonction;
use App\Entity\Institution;
use App\Entity\Pointage;
use App\Entity\Service;
use App\Entity\Structure;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Service\AgentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class Agent360Test extends WebTestCase
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
    private Service $serviceA;
    private Fonction $fonctionA;
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

        // 1. Tenants
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Agent Alpha ' . uniqid());
        $this->tenantA->setCode('TAA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Agent Beta ' . uniqid());
        $this->tenantB->setCode('TAB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Users
        $this->userA = $this->createUser('admin_agent_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->userB = $this->createUser('admin_agent_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 3. Structures
        $this->structureA = new Structure();
        $this->structureA->setNom('Direction Générale Alpha');
        $this->structureA->setCode('DGA-' . uniqid());
        $this->structureA->setType(Structure::TYPE_DIRECTION_GENERALE);
        $this->structureA->setInstitution($this->tenantA);
        $this->em->persist($this->structureA);

        $this->structureB = new Structure();
        $this->structureB->setNom('Direction Générale Beta');
        $this->structureB->setCode('DGB-' . uniqid());
        $this->structureB->setType(Structure::TYPE_DIRECTION_GENERALE);
        $this->structureB->setInstitution($this->tenantB);
        $this->em->persist($this->structureB);

        // 4. Service & Fonction
        $this->serviceA = new Service();
        $this->serviceA->setNom('Service RH Alpha');
        $this->serviceA->setInstitution($this->tenantA);
        $this->serviceA->setStructure($this->structureA);
        $this->em->persist($this->serviceA);

        $this->fonctionA = new Fonction();
        $this->fonctionA->setLibelle('Gestionnaire RH');
        $this->em->persist($this->fonctionA);

        // 5. Employes
        $this->employeA = new Employe();
        $this->employeA->setNom('Kaboré');
        $this->employeA->setPrenom('Moussa');
        $this->employeA->setEmail('moussa.kabore_' . uniqid() . '@alpha.com');
        $this->employeA->setMatricule('MAT-A-' . uniqid());
        $this->employeA->setDateNaissance(new \DateTime('1990-05-15'));
        $this->employeA->setLieuNaissance('N\'Djaména');
        $this->employeA->setSexe('M');
        $this->employeA->setNationalite('Tchadienne');
        $this->employeA->setDateEmbauche(new \DateTime('2021-01-10'));
        $this->employeA->setDatePriseService(new \DateTime('2021-01-15'));
        $this->employeA->setStatutAdministratif(Employe::STATUT_ADMIN_FONCTIONNAIRE);
        $this->employeA->setSituationAdministrative(Employe::SITUATION_EN_ACTIVITE);
        $this->employeA->setCorps('Administration générale');
        $this->employeA->setGrade('Principal');
        $this->employeA->setEchelon('3');
        $this->employeA->setCategorie('A1');
        $this->employeA->setRegime('Régime général');
        $this->employeA->setSalaire(450000.00);
        $this->employeA->setActif(true);
        $this->employeA->setUser($this->userA);
        $this->employeA->setInstitution($this->tenantA);
        $this->employeA->setStructure($this->structureA);
        $this->employeA->setService($this->serviceA);
        $this->employeA->setFonction($this->fonctionA);
        $this->em->persist($this->employeA);

        $this->employeB = new Employe();
        $this->employeB->setNom('Diallo');
        $this->employeB->setPrenom('Aminata');
        $this->employeB->setEmail('aminata.diallo_' . uniqid() . '@beta.com');
        $this->employeB->setMatricule('MAT-B-' . uniqid());
        $this->employeB->setDateNaissance(new \DateTime('1994-08-20'));
        $this->employeB->setDateEmbauche(new \DateTime('2022-03-01'));
        $this->employeB->setSalaire(380000.00);
        $this->employeB->setActif(true);
        $this->employeB->setUser($this->userB);
        $this->employeB->setInstitution($this->tenantB);
        $this->employeB->setStructure($this->structureB);
        $this->employeB->setFonction($this->fonctionA);
        $this->em->persist($this->employeB);

        $this->em->flush();
    }

    private function createUser(string $email, array $roles, Institution $institution): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setInstitution($institution);
        $user->setIsVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, 'Password123!'));
        $this->em->persist($user);
        return $user;
    }

    public function testFicheAgent360DisplaysAllTabsAndKpis(): void
    {
        $this->client->loginUser($this->userA);

        $crawler = $this->client->request('GET', '/admin/employe/' . $this->employeA->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Kaboré Moussa');
        $this->assertSelectorTextContains('.profile-header-card', $this->employeA->getMatricule());
        $this->assertSelectorTextContains('.profile-header-card', 'Fonctionnaire');
        $this->assertSelectorTextContains('.profile-header-card', 'En activité');

        // Check tabs
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Vue Générale', $content);
        $this->assertStringContainsString('Identité & État Civil', $content);
        $this->assertStringContainsString('Situation Administrative', $content);
        $this->assertStringContainsString('Organisation & Affectation', $content);
        $this->assertStringContainsString('Carrière & Postes', $content);
        $this->assertStringContainsString('Congés & Absences', $content);
        $this->assertStringContainsString('Présences & Pointages', $content);
        $this->assertStringContainsString('Documents RH', $content);
        $this->assertStringContainsString('Actes Administratifs', $content);
        $this->assertStringContainsString('Chronologie 360°', $content);

        // Check KPIs
        $this->assertStringContainsString('Ancienneté', $content);
        $this->assertStringContainsString('Solde Congés Restant', $content);
        $this->assertStringContainsString('Taux de Présence', $content);
        $this->assertStringContainsString('Complétude Dossier', $content);

        // Check administrative data
        $this->assertStringContainsString('Administration générale', $content);
        $this->assertStringContainsString('Principal', $content);
        $this->assertStringContainsString('A1', $content);
        $this->assertStringContainsString('Direction Générale Alpha', $content);
    }

    public function testMultiTenancyStrictIsolationOnAgentShow(): void
    {
        // User A tries to view Employe B (from Tenant Beta)
        $this->client->loginUser($this->userA);

        $this->client->request('GET', '/admin/employe/' . $this->employeB->getId());

        // Must either be 403 Forbidden or 404 Not Found (via TenantFilter)
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [403, 404], true),
            sprintf('Cross-tenant employee view must return 403 or 404, got %d', $statusCode)
        );
    }

    public function testMultiTenancyStrictIsolationOnAgentEdit(): void
    {
        // User A tries to edit Employe B
        $this->client->loginUser($this->userA);

        $this->client->request('GET', '/admin/employe/' . $this->employeB->getId() . '/edit');

        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [403, 404], true),
            sprintf('Cross-tenant employee edit must return 403 or 404, got %d', $statusCode)
        );
    }

    public function testMultiTenancyStrictIsolationOnAgentSolde(): void
    {
        // User A tries to query solde for Employe B
        $this->client->loginUser($this->userA);

        $this->client->request('GET', '/admin/employe/' . $this->employeB->getId() . '/solde');

        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [403, 404], true),
            sprintf('Cross-tenant employee solde must return 403 or 404, got %d', $statusCode)
        );
    }

    public function testAgentServiceCalculationsAndTimeline(): void
    {
        /** @var AgentService $service */
        $service = static::getContainer()->get(AgentService::class);

        // Add a pointage
        $pointage = new Pointage();
        $pointage->setEmploye($this->employeA);
        $pointage->setInstitution($this->tenantA);
        $pointage->setDate(new \DateTime('yesterday'));
        $pointage->setDateEntree(new \DateTime('yesterday 08:00:00'));
        $pointage->setDateSortie(new \DateTime('yesterday 17:00:00'));
        $pointage->setEstPresent(true);
        $this->em->persist($pointage);
        $this->em->flush();

        $view = $service->getAgent360View($this->employeA);

        $this->assertIsArray($view);
        $this->assertArrayHasKey('age', $view);
        $this->assertGreaterThan(30, $view['age']);

        $this->assertArrayHasKey('anciennete', $view);
        $this->assertGreaterThanOrEqual(3, $view['anciennete']['annees']);

        $this->assertArrayHasKey('statutAdmin', $view);
        $this->assertSame('Fonctionnaire', $view['statutAdmin']['label']);

        $this->assertArrayHasKey('situationAdmin', $view);
        $this->assertSame('En activité', $view['situationAdmin']['label']);

        $this->assertArrayHasKey('pointages', $view);
        $this->assertSame(100, $view['pointages']['tauxPresence']);

        $this->assertArrayHasKey('timeline', $view);
        $this->assertNotEmpty($view['timeline']);

        $this->assertArrayHasKey('completion', $view);
        $this->assertGreaterThan(50, $view['completion']['score']);
    }

    public function testAgentUpdateAdministrativeSituation(): void
    {
        $this->client->loginUser($this->userA);

        $crawler = $this->client->request('GET', '/admin/employe/' . $this->employeA->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Mettre à jour l\'agent')->form([
            'employe[nom]' => 'Kaboré Modifié',
            'employe[prenom]' => 'Moussa',
            'employe[email]' => $this->employeA->getEmail(),
            'employe[matricule]' => $this->employeA->getMatricule(),
            'employe[salaire]' => 500000.00,
            'employe[situation_administrative]' => Employe::SITUATION_DETACHEMENT,
            'employe[grade]' => 'Hors classe',
            'employe[statut]' => '1',
            'employe[fonction]' => (string) $this->fonctionA->getId(),
            'employe[date_embauche]' => '2021-01-10',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/employe');

        $this->em->clear();
        $updatedEmploye = $this->em->getRepository(Employe::class)->find($this->employeA->getId());
        $this->assertSame('Kaboré Modifié', $updatedEmploye->getNom());
        $this->assertSame(Employe::SITUATION_DETACHEMENT, $updatedEmploye->getSituationAdministrative());
        $this->assertSame('Hors classe', $updatedEmploye->getGrade());
        $this->assertEquals(500000.00, $updatedEmploye->getSalaire());
    }

    public function testAgentCreationWithAllAdministrativeFields(): void
    {
        $this->client->loginUser($this->userA);

        $crawler = $this->client->request('GET', '/admin/employe/new');
        $this->assertResponseIsSuccessful();

        $matricule = 'MAT-NEW-' . uniqid();
        $email = 'new.agent_' . uniqid() . '@alpha.com';

        $form = $crawler->selectButton('Créer l\'agent')->form([
            'employe[nom]' => 'Oumar',
            'employe[prenom]' => 'Fatimé',
            'employe[email]' => $email,
            'employe[matricule]' => $matricule,
            'employe[date_naissance]' => '1995-02-14',
            'employe[lieu_naissance]' => 'Moundou',
            'employe[sexe]' => 'F',
            'employe[nationalite]' => 'Tchadienne',
            'employe[numero_telephone]' => '+235 66 11 22 33',
            'employe[adresse]' => 'Quartier Administratif',
            'employe[structure]' => (string) $this->structureA->getId(),
            'employe[service]' => (string) $this->serviceA->getId(),
            'employe[fonction]' => (string) $this->fonctionA->getId(),
            'employe[statut_administratif]' => Employe::STATUT_ADMIN_CONTRACTUEL,
            'employe[situation_administrative]' => Employe::SITUATION_EN_ACTIVITE,
            'employe[corps]' => 'Cadre technique',
            'employe[grade]' => 'Agent technique supérieur',
            'employe[echelon]' => '2',
            'employe[categorie]' => 'B1',
            'employe[regime]' => 'Spécial',
            'employe[date_embauche]' => '2023-06-01',
            'employe[date_prise_service]' => '2023-06-15',
            'employe[salaire]' => 320000.00,
            'employe[statut]' => '1',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/employe');

        $this->em->clear();
        $newEmploye = $this->em->getRepository(Employe::class)->findOneBy(['matricule' => $matricule]);
        $this->assertNotNull($newEmploye);
        $this->assertSame('Oumar', $newEmploye->getNom());
        $this->assertSame('Fatimé', $newEmploye->getPrenom());
        $this->assertSame('Moundou', $newEmploye->getLieuNaissance());
        $this->assertSame('F', $newEmploye->getSexe());
        $this->assertSame('Tchadienne', $newEmploye->getNationalite());
        $this->assertSame('Cadre technique', $newEmploye->getCorps());
        $this->assertSame('Agent technique supérieur', $newEmploye->getGrade());
        $this->assertSame('B1', $newEmploye->getCategorie());
        $this->assertSame($this->structureA->getId(), $newEmploye->getStructure()->getId());
        $this->assertSame($this->tenantA->getId(), $newEmploye->getInstitution()->getId());
    }
}
