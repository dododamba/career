<?php

namespace App\Tests\Poste;

use App\Entity\Employe;
use App\Entity\Fonction;
use App\Entity\Institution;
use App\Entity\OccupationPoste;
use App\Entity\Poste;
use App\Entity\Service;
use App\Entity\Structure;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Service\PosteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PosteTest extends WebTestCase
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
    private Employe $employeA1;
    private Employe $employeA2;
    private Employe $employeB;
    private Poste $posteA;
    private Poste $posteB;

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
        $this->tenantA->setNom('Tenant Poste Alpha ' . uniqid());
        $this->tenantA->setCode('TPA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Poste Beta ' . uniqid());
        $this->tenantB->setCode('TPB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Users
        $this->userA = $this->createUser('admin_poste_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->userB = $this->createUser('admin_poste_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 3. Structures
        $this->structureA = new Structure();
        $this->structureA->setNom('Direction des Systèmes d\'Information');
        $this->structureA->setCode('DSI-' . uniqid());
        $this->structureA->setType(Structure::TYPE_DIRECTION);
        $this->structureA->setInstitution($this->tenantA);
        $this->em->persist($this->structureA);

        $this->structureB = new Structure();
        $this->structureB->setNom('Direction Administrative Beta');
        $this->structureB->setCode('DAB-' . uniqid());
        $this->structureB->setType(Structure::TYPE_DIRECTION);
        $this->structureB->setInstitution($this->tenantB);
        $this->em->persist($this->structureB);

        // 4. Service & Fonction
        $this->serviceA = new Service();
        $this->serviceA->setNom('Service Développement');
        $this->serviceA->setInstitution($this->tenantA);
        $this->serviceA->setStructure($this->structureA);
        $this->em->persist($this->serviceA);

        $this->fonctionA = new Fonction();
        $this->fonctionA->setLibelle('Ingénieur Système');
        $this->em->persist($this->fonctionA);

        // 5. Employes
        $this->employeA1 = new Employe();
        $this->employeA1->setNom('Mahamat');
        $this->employeA1->setPrenom('Ali');
        $this->employeA1->setEmail('ali.mahamat_' . uniqid() . '@alpha.com');
        $this->employeA1->setMatricule('EMP-A1-' . uniqid());
        $this->employeA1->setDateEmbauche(new \DateTime('2022-01-01'));
        $this->employeA1->setSalaire(500000.00);
        $this->employeA1->setActif(true);
        $this->employeA1->setUser($this->userA);
        $this->employeA1->setInstitution($this->tenantA);
        $this->employeA1->setStructure($this->structureA);
        $this->em->persist($this->employeA1);

        $this->employeA2 = new Employe();
        $this->employeA2->setNom('Nodji');
        $this->employeA2->setPrenom('Blaise');
        $this->employeA2->setEmail('blaise.nodji_' . uniqid() . '@alpha.com');
        $this->employeA2->setMatricule('EMP-A2-' . uniqid());
        $this->employeA2->setDateEmbauche(new \DateTime('2023-02-01'));
        $this->employeA2->setSalaire(420000.00);
        $this->employeA2->setActif(true);
        $this->employeA2->setUser($this->userA);
        $this->employeA2->setInstitution($this->tenantA);
        $this->employeA2->setStructure($this->structureA);
        $this->em->persist($this->employeA2);

        $this->employeB = new Employe();
        $this->employeB->setNom('Sow');
        $this->employeB->setPrenom('Ousmane');
        $this->employeB->setEmail('ousmane.sow_' . uniqid() . '@beta.com');
        $this->employeB->setMatricule('EMP-B1-' . uniqid());
        $this->employeB->setDateEmbauche(new \DateTime('2023-01-01'));
        $this->employeB->setSalaire(390000.00);
        $this->employeB->setActif(true);
        $this->employeB->setUser($this->userB);
        $this->employeB->setInstitution($this->tenantB);
        $this->employeB->setStructure($this->structureB);
        $this->em->persist($this->employeB);

        // 6. Postes
        $this->posteA = new Poste();
        $this->posteA->setReference('PST-DSI-001');
        $this->posteA->setIntitule('Responsable des Infrastructures et Réseaux');
        $this->posteA->setNiveau(Poste::NIVEAU_CADRE_SUPERIEUR);
        $this->posteA->setStatut(Poste::STATUT_VACANT);
        $this->posteA->setInstitution($this->tenantA);
        $this->posteA->setStructure($this->structureA);
        $this->posteA->setService($this->serviceA);
        $this->posteA->setFonction($this->fonctionA);
        $this->posteA->setDescription('Garantir la haute disponibilité du système d\'information.');
        $this->posteA->setCompetencesRequises('Bac+5 en Informatique, 5 ans d\'expérience réseau.');
        $this->em->persist($this->posteA);

        $this->posteB = new Poste();
        $this->posteB->setReference('PST-BETA-001');
        $this->posteB->setIntitule('Contrôleur de Gestion Beta');
        $this->posteB->setNiveau(Poste::NIVEAU_CADRE);
        $this->posteB->setStatut(Poste::STATUT_VACANT);
        $this->posteB->setInstitution($this->tenantB);
        $this->posteB->setStructure($this->structureB);
        $this->em->persist($this->posteB);

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

    public function testCataloguePostesDisplayAndKpis(): void
    {
        $this->client->loginUser($this->userA);

        $this->client->request('GET', '/admin/poste');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Gestion des Postes & Nomenclature RH', $content);
        $this->assertStringContainsString('Total Postes', $content);
        $this->assertStringContainsString('Postes Occupés', $content);
        $this->assertStringContainsString('Postes Vacants', $content);
        $this->assertStringContainsString('Taux d\'Occupation', $content);
        $this->assertStringContainsString('Responsable des Infrastructures et Réseaux', $content);
        $this->assertStringContainsString('PST-DSI-001', $content);
    }

    public function testPosteVacantsList(): void
    {
        $this->client->loginUser($this->userA);

        $this->client->request('GET', '/admin/poste/vacants');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Postes Vacants & Recrutements à Pourvoir', $content);
        $this->assertStringContainsString('PST-DSI-001', $content);
        $this->assertStringContainsString('Affecter un agent', $content);
    }

    public function testPosteCreation(): void
    {
        $this->client->loginUser($this->userA);

        $crawler = $this->client->request('GET', '/admin/poste/new');
        $this->assertResponseIsSuccessful();

        $ref = 'PST-DEV-' . uniqid();
        $form = $crawler->selectButton('Créer le poste')->form([
            'poste[reference]' => $ref,
            'poste[intitule]' => 'Développeur Fullstack Senior',
            'poste[niveau]' => Poste::NIVEAU_CADRE,
            'poste[statut]' => Poste::STATUT_VACANT,
            'poste[structure]' => (string) $this->structureA->getId(),
            'poste[service]' => (string) $this->serviceA->getId(),
            'poste[description]' => 'Conception et maintenance d\'applications métier.',
            'poste[competences_requises]' => 'Maîtrise de Symfony, PHP 8, API REST, SQL.',
            'poste[date_creation]' => '2024-01-15',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects();

        $this->em->clear();
        $newPoste = $this->em->getRepository(Poste::class)->findOneBy(['reference' => $ref]);
        $this->assertNotNull($newPoste);
        $this->assertSame('Développeur Fullstack Senior', $newPoste->getIntitule());
        $this->assertSame($this->tenantA->getId(), $newPoste->getInstitution()->getId());
        $this->assertSame(Poste::STATUT_VACANT, $newPoste->getStatut());
        $this->assertTrue($newPoste->isVacant());
    }

    public function testFichePoste360View(): void
    {
        $this->client->loginUser($this->userA);

        $this->client->request('GET', '/admin/poste/' . $this->posteA->getId());

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Fiche de Poste 360°', $content);
        $this->assertStringContainsString('Responsable des Infrastructures et Réseaux', $content);
        $this->assertStringContainsString('PST-DSI-001', $content);
        $this->assertStringContainsString('Ce poste est actuellement vacant', $content);
        $this->assertStringContainsString('Missions & Activités Principales', $content);
        $this->assertStringContainsString('Compétences & Profil Recherché', $content);
        $this->assertStringContainsString('Historique des Occupations du Poste', $content);
    }

    public function testPosteAffectationCycleDeVie(): void
    {
        /** @var PosteService $service */
        $service = static::getContainer()->get(PosteService::class);

        // 1. Initial state: poste is vacant
        $this->assertTrue($this->posteA->isVacant());
        $this->assertNull($this->posteA->getOccupantActuel());
        $this->assertNull($this->employeA1->getPoste());

        // 2. Affecter l'agent A1 au poste A
        $dateDebut = new \DateTime('2024-03-01');
        $occ1 = $service->affecterAgent(
            $this->posteA,
            $this->employeA1,
            $dateDebut,
            OccupationPoste::MOTIF_RECRUTEMENT,
            'Nomination officielle par arrêté RH-2024-01'
        );

        $this->assertNotNull($occ1);
        $this->assertTrue($occ1->isActif());
        $this->assertSame($this->employeA1->getId(), $this->posteA->getOccupantActuel()->getId());
        $this->assertSame(Poste::STATUT_OCCUPE, $this->posteA->getStatut());
        $this->assertSame($this->posteA->getId(), $this->employeA1->getPoste()->getId());

        // 3. Libérer le poste
        $dateFin = new \DateTime('2024-12-31');
        $service->libererPoste($this->posteA, $dateFin, 'Mutation externe');

        $this->em->clear();
        $reloadedPoste = $this->em->getRepository(Poste::class)->find($this->posteA->getId());
        $this->assertTrue($reloadedPoste->isVacant());
        $this->assertNull($reloadedPoste->getOccupantActuel());

        $reloadedEmploye = $this->em->getRepository(Employe::class)->find($this->employeA1->getId());
        $this->assertNull($reloadedEmploye->getPoste());

        $occupations = $this->em->getRepository(OccupationPoste::class)->findBy(['poste' => $this->posteA]);
        $this->assertCount(1, $occupations);
        $this->assertFalse($occupations[0]->isActif());
        $this->assertEquals($dateFin->format('Y-m-d'), $occupations[0]->getDateFin()->format('Y-m-d'));
        $this->assertStringContainsString('Mutation externe', $occupations[0]->getCommentaire());
    }

    public function testPosteReplacementClosesPreviousOccupant(): void
    {
        /** @var PosteService $service */
        $service = static::getContainer()->get(PosteService::class);

        // Affecter Employe A1
        $service->affecterAgent($this->posteA, $this->employeA1, new \DateTime('2023-01-01'));
        $this->assertSame($this->employeA1->getId(), $this->posteA->getOccupantActuel()->getId());

        // Remplacer par Employe A2
        $service->affecterAgent($this->posteA, $this->employeA2, new \DateTime('2024-06-01'), OccupationPoste::MOTIF_PROMOTION);

        $this->em->clear();
        $reloadedPoste = $this->em->getRepository(Poste::class)->find($this->posteA->getId());
        $this->assertSame($this->employeA2->getId(), $reloadedPoste->getOccupantActuel()->getId());

        // Vérifier l'ancien occupant libéré
        $reloadedA1 = $this->em->getRepository(Employe::class)->find($this->employeA1->getId());
        $this->assertNull($reloadedA1->getPoste());

        // Vérifier le nouvel occupant assigné
        $reloadedA2 = $this->em->getRepository(Employe::class)->find($this->employeA2->getId());
        $this->assertSame($this->posteA->getId(), $reloadedA2->getPoste()->getId());

        // Vérifier l'historique complet (2 occupations)
        $history = $this->em->getRepository(OccupationPoste::class)->findBy(['poste' => $this->posteA], ['date_debut' => 'ASC']);
        $this->assertCount(2, $history);
        $this->assertFalse($history[0]->isActif()); // A1 terminé
        $this->assertTrue($history[1]->isActif());  // A2 actif
    }

    public function testMultiTenancyStrictIsolationOnPoste(): void
    {
        $this->client->loginUser($this->userA);

        // User A tries to view Poste B (from Tenant Beta)
        $this->client->request('GET', '/admin/poste/' . $this->posteB->getId());
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($statusCode, [403, 404], true));

        // User A tries to edit Poste B
        $this->client->request('GET', '/admin/poste/' . $this->posteB->getId() . '/edit');
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($statusCode, [403, 404], true));

        // IDOR Prevention in Service: trying to assign Tenant Beta employee to Tenant Alpha poste throws AccessDeniedException
        /** @var PosteService $service */
        $service = static::getContainer()->get(PosteService::class);
        $this->expectException(AccessDeniedException::class);
        $service->affecterAgent($this->posteA, $this->employeB, new \DateTime());
    }
}
