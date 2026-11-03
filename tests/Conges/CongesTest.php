<?php

namespace App\Tests\Conges;

use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\SoldeConge;
use App\Entity\TypeActe;
use App\Entity\TypeConges;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Repository\CongesRepository;
use App\Repository\SoldeCongeRepository;
use App\Repository\TypeCongesRepository;
use App\Service\CongeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CongesTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private CongeService $congeService;
    private CongesRepository $congesRepository;
    private SoldeCongeRepository $soldeCongeRepository;
    private TypeCongesRepository $typeCongesRepository;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $adminA;
    private User $managerA;
    private User $agentA;
    private User $adminB;
    private Employe $employeA;
    private Employe $employeManager;
    private Employe $employeB;
    private TypeConges $typeAnnuel;
    private TypeConges $typeMaladie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->congeService = $this->client->getContainer()->get(CongeService::class);
        $this->congesRepository = $this->client->getContainer()->get(CongesRepository::class);
        $this->soldeCongeRepository = $this->client->getContainer()->get(SoldeCongeRepository::class);
        $this->typeCongesRepository = $this->client->getContainer()->get(TypeCongesRepository::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Tenants
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Conges Alpha ' . uniqid());
        $this->tenantA->setCode('TCA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Conges Beta ' . uniqid());
        $this->tenantB->setCode('TCB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Users
        $this->adminA = $this->createUser('admin_conge_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->managerA = $this->createUser('mgr_conge_a_' . uniqid() . '@alpha.com', ['ROLE_MANAGER'], $this->tenantA);
        $this->agentA = $this->createUser('agent_conge_a_' . uniqid() . '@alpha.com', ['ROLE_USER'], $this->tenantA);
        $this->adminB = $this->createUser('admin_conge_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 3. Employes
        $this->employeA = new Employe();
        $this->employeA->setNom('KOUAME')
            ->setPrenom('Agent Alpha')
            ->setMatricule('MAT-CGA-' . uniqid())
            ->setUser($this->agentA)
            ->setInstitution($this->tenantA)
            ->setSalaire(3000.0)
            ->setActif(true);
        $this->em->persist($this->employeA);

        $this->employeManager = new Employe();
        $this->employeManager->setNom('DIALLO')
            ->setPrenom('Manager Alpha')
            ->setMatricule('MAT-MGR-' . uniqid())
            ->setUser($this->managerA)
            ->setInstitution($this->tenantA)
            ->setSalaire(4500.0)
            ->setActif(true);
        $this->em->persist($this->employeManager);

        $this->employeB = new Employe();
        $this->employeB->setNom('TRAORE')
            ->setPrenom('Agent Beta')
            ->setMatricule('MAT-CGB-' . uniqid())
            ->setUser($this->adminB)
            ->setInstitution($this->tenantB)
            ->setSalaire(3200.0)
            ->setActif(true);
        $this->em->persist($this->employeB);

        // 4. Types de congés
        $this->typeAnnuel = $this->typeCongesRepository->findByCode(TypeConges::CODE_ANNUEL);
        if (!$this->typeAnnuel) {
            $this->typeAnnuel = new TypeConges();
            $this->typeAnnuel->setCode(TypeConges::CODE_ANNUEL)
                ->setLibelle('Congé Annuel')
                ->setJoursParAn(30)
                ->setIsDeductible(true)
                ->setJustificatifRequis(false);
            $this->em->persist($this->typeAnnuel);
        }

        $this->typeMaladie = $this->typeCongesRepository->findByCode(TypeConges::CODE_MALADIE);
        if (!$this->typeMaladie) {
            $this->typeMaladie = new TypeConges();
            $this->typeMaladie->setCode(TypeConges::CODE_MALADIE)
                ->setLibelle('Congé de Maladie')
                ->setJoursParAn(90)
                ->setIsDeductible(false)
                ->setJustificatifRequis(true);
            $this->em->persist($this->typeMaladie);
        }

        // Assurer l'existence du TypeActe TITRE_CONGE
        $typeActeRepo = $this->em->getRepository(TypeActe::class);
        $typeTitreConge = $typeActeRepo->findOneBy(['code' => 'TITRE_CONGE']);
        if (!$typeTitreConge) {
            $typeTitreConge = new TypeActe();
            $typeTitreConge->setCode('TITRE_CONGE')
                ->setLibelle('Titre de congé')
                ->setNature('DECISION')
                ->setActif(true);
            $this->em->persist($typeTitreConge);
        }

        $this->em->flush();
    }

    private function createUser(string $email, array $roles, Institution $institution): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setInstitution($institution);
        $user->setPassword($this->hasher->hashPassword($user, 'Password123!'));
        $this->em->persist($user);

        return $user;
    }

    public function testDemanderCongeAvecSucces(): void
    {
        $dateDebut = new \DateTime('+5 days');
        $dateFin = new \DateTime('+9 days'); // 5 jours calendaires

        $conge = $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: $dateDebut,
            dateFin: $dateFin,
            motif: 'Repos annuel bien mérité',
            demandeur: $this->agentA
        );

        $this->assertNotNull($conge->getId());
        $this->assertEquals(Conges::STATUT_EN_ATTENTE, $conge->getStatut());
        $this->assertEquals(5, $conge->getDateDiff());
        $this->assertEquals($this->tenantA->getId(), $conge->getInstitution()->getId());
        $this->assertCount(1, $conge->getCongesLogs());
        $this->assertEquals('create', $conge->getCongesLogs()->first()->getAction());
    }

    public function testDemanderCongeDateFinAnterieure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin ne peut pas être antérieure à la date de début.');

        $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+10 days'),
            dateFin: new \DateTime('+5 days'),
            demandeur: $this->agentA
        );
    }

    public function testDemanderCongeAntiChevauchement(): void
    {
        // 1er congé valide
        $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+15 days'),
            dateFin: new \DateTime('+20 days'),
            demandeur: $this->agentA
        );

        // 2e congé chevauchant
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('L\'employé a déjà une demande de congé ou absence en cours ou validée sur cette période.');

        $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+18 days'),
            dateFin: new \DateTime('+25 days'),
            demandeur: $this->agentA
        );
    }

    public function testDemanderCongeJustificatifRequis(): void
    {
        // Sans justificatif alors qu'il est obligatoire pour Maladie
        try {
            $this->congeService->demanderConge(
                employe: $this->employeA,
                typeConge: $this->typeMaladie,
                dateDebut: new \DateTime('+30 days'),
                dateFin: new \DateTime('+35 days'),
                document: null,
                demandeur: $this->agentA
            );
            $this->fail('Une LogicException aurait dû être levée pour justificatif manquant.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Un document justificatif est obligatoire', $e->getMessage());
        }

        // Créer un document justificatif valide
        $doc = new Document();
        $doc->setTitre('Certificat Médical')
            ->setReference('CM-2026-001')
            ->setCategorie(Document::CATEGORIE_MEDICAL)
            ->setFichierNomOriginal('certificat.pdf')
            ->setFichierNomStockage('tenant_' . $this->tenantA->getId() . '/certificat.pdf')
            ->setTaille(1024)
            ->setTypeMime('application/pdf')
            ->setFichierHash(hash('sha256', 'certificat_medical'))
            ->setInstitution($this->tenantA)
            ->setEmploye($this->employeA);
        $this->em->persist($doc);
        $this->em->flush();

        $conge = $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeMaladie,
            dateDebut: new \DateTime('+30 days'),
            dateFin: new \DateTime('+35 days'),
            document: $doc,
            demandeur: $this->agentA
        );

        $this->assertNotNull($conge->getId());
        $this->assertEquals($doc->getId(), $conge->getDocument()->getId());
    }

    public function testDemanderCongeSoldeInsuffisant(): void
    {
        $annee = (int) date('Y');
        // Initialiser artificiellement un solde faible
        $solde = new SoldeConge();
        $solde->setEmploye($this->employeA)
            ->setTypeConge($this->typeAnnuel)
            ->setInstitution($this->tenantA)
            ->setAnnee($annee)
            ->setSoldeAcquis('30.00')
            ->setSoldePris('28.00')
            ->setSolde('2.00'); // seulement 2 jours restants
        $this->em->persist($solde);
        $this->em->flush();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solde de congés insuffisant');

        // Demande de 5 jours
        $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+40 days'),
            dateFin: new \DateTime('+44 days'),
            demandeur: $this->agentA
        );
    }

    public function testApprouverCongeDeduitSoldeEtCreeActe(): void
    {
        $annee = (int) date('Y');
        // Réinitialiser le solde à 30 jours
        $solde = $this->soldeCongeRepository->findOneByEmployeAndTypeAndAnnee($this->employeA, $this->typeAnnuel, $annee);
        if (!$solde) {
            $solde = new SoldeConge();
            $solde->setEmploye($this->employeA)
                ->setTypeConge($this->typeAnnuel)
                ->setInstitution($this->tenantA)
                ->setAnnee($annee)
                ->setSoldeAcquis('30.00')
                ->setSoldePris('0.00')
                ->setSolde('30.00');
            $this->em->persist($solde);
            $this->em->flush();
        } else {
            $solde->setSoldeAcquis('30.00')->setSoldePris('0.00')->setSolde('30.00');
            $this->em->flush();
        }

        // Créer un congé de 4 jours (ex: dans 50 jours)
        $conge = $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+50 days'),
            dateFin: new \DateTime('+53 days'),
            demandeur: $this->agentA
        );

        $this->assertEquals(4, $conge->getDateDiff());

        // Approbation par le manager
        $congeApprouve = $this->congeService->approuverConge(
            conge: $conge,
            approbateur: $this->managerA,
            genererActe: true
        );

        $this->assertEquals(Conges::STATUT_APPROUVE, $congeApprouve->getStatut());
        $this->assertEquals($this->managerA->getId(), $congeApprouve->getApprouvePar()->getId());
        $this->assertNotNull($congeApprouve->getDateApprobation());

        // Vérifier déduction du solde
        $soldeMisAJour = $this->soldeCongeRepository->findOneByEmployeAndTypeAndAnnee($this->employeA, $this->typeAnnuel, $annee);
        $this->assertEquals('26.00', $soldeMisAJour->getSolde());
        $this->assertEquals('4.00', $soldeMisAJour->getSoldePris());

        // Vérifier la génération de l'acte administratif
        $this->assertNotNull($congeApprouve->getActe());
        $this->assertEquals(Acte::STATUT_SIGNE, $congeApprouve->getActe()->getStatut());
        $this->assertEquals($this->tenantA->getId(), $congeApprouve->getActe()->getInstitution()->getId());
    }

    public function testApprouverCongeAntiSelfApproval(): void
    {
        // Manager demande un congé pour lui-même
        $congeManager = $this->congeService->demanderConge(
            employe: $this->employeManager,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+60 days'),
            dateFin: new \DateTime('+63 days'),
            demandeur: $this->managerA
        );

        // Le manager tente d'approuver son propre congé
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas approuver votre propre demande de congé.');

        $this->congeService->approuverConge(
            conge: $congeManager,
            approbateur: $this->managerA
        );
    }

    public function testRejeterConge(): void
    {
        $conge = $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+70 days'),
            dateFin: new \DateTime('+72 days'),
            demandeur: $this->agentA
        );

        $annee = (int) date('Y');
        $soldeAvant = (float) $this->soldeCongeRepository->findOneByEmployeAndTypeAndAnnee($this->employeA, $this->typeAnnuel, $annee)?->getSolde();

        $congeRejete = $this->congeService->rejeterConge(
            conge: $conge,
            refuseur: $this->managerA,
            motifRefus: 'Nécessité de service impérieuse'
        );

        $this->assertEquals(Conges::STATUT_REFUSE, $congeRejete->getStatut());
        $this->assertEquals('Nécessité de service impérieuse', $congeRejete->getCommentaireRefus());

        // Vérifier que le solde n'a pas été déduit
        $soldeApres = (float) $this->soldeCongeRepository->findOneByEmployeAndTypeAndAnnee($this->employeA, $this->typeAnnuel, $annee)?->getSolde();
        $this->assertEquals($soldeAvant, $soldeApres);
    }

    public function testAnnulerCongeApprouveRecrediteSolde(): void
    {
        $annee = (int) date('Y');
        // Initialiser solde
        $solde = $this->soldeCongeRepository->findOneByEmployeAndTypeAndAnnee($this->employeA, $this->typeAnnuel, $annee);
        if (!$solde) {
            $solde = new SoldeConge();
            $solde->setEmploye($this->employeA)
                ->setTypeConge($this->typeAnnuel)
                ->setInstitution($this->tenantA)
                ->setAnnee($annee)
                ->setSoldeAcquis('30.00');
            $this->em->persist($solde);
        }
        $solde->setSolde('26.00')->setSoldePris('4.00');
        $this->em->flush();

        // Créer et approuver 3 jours
        $conge = $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+80 days'),
            dateFin: new \DateTime('+82 days'), // 3 jours
            demandeur: $this->agentA
        );

        $this->congeService->approuverConge($conge, $this->managerA, genererActe: true);

        // Solde = 23.00, SoldePris = 7.00
        $soldeApresApprobation = $this->soldeCongeRepository->findOneByEmployeAndTypeAndAnnee($this->employeA, $this->typeAnnuel, $annee);
        $this->assertEquals('23.00', $soldeApresApprobation->getSolde());

        // Annulation par admin
        $congeAnnule = $this->congeService->annulerConge($conge, $this->adminA, 'Annulation par convenance');

        $this->assertEquals(Conges::STATUT_ANNULE, $congeAnnule->getStatut());

        // Vérifier que les 3 jours ont été recrédités : solde redevient 26.00
        $soldeApresAnnulation = $this->soldeCongeRepository->findOneByEmployeAndTypeAndAnnee($this->employeA, $this->typeAnnuel, $annee);
        $this->assertEquals('26.00', $soldeApresAnnulation->getSolde());
        $this->assertEquals('4.00', $soldeApresAnnulation->getSoldePris());

        // Vérifier que l'acte associé a été abrogé
        $this->assertEquals(Acte::STATUT_ABROGE, $congeAnnule->getActe()->getStatut());
    }

    public function testMultiTenantIsolationFailClosed(): void
    {
        // Créer un congé pour Tenant A
        $congeA = $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $this->typeAnnuel,
            dateDebut: new \DateTime('+90 days'),
            dateFin: new \DateTime('+92 days'),
            demandeur: $this->agentA
        );

        // Se connecter avec l'admin de Tenant B
        $this->client->loginUser($this->adminB);

        // Tentative d'accéder au congé du Tenant A (doit être refusé par Voter ou invisible par TenantFilter)
        $this->client->request('GET', '/admin/conges/' . $congeA->getId());
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [403, 404], true),
            sprintf('L\'accès cross-tenant au congé doit être 403 ou 404, reçu : %d', $statusCode)
        );

        // Tentative d'approuver le congé du Tenant A depuis Tenant B
        $this->client->request('POST', '/admin/conges/' . $congeA->getId() . '/approve', [
            '_token' => 'invalid_or_valid',
        ]);
        $postStatusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($postStatusCode, [403, 404], true),
            sprintf('L\'approbation cross-tenant du congé doit être 403 ou 404, reçu : %d', $postStatusCode)
        );
    }

    public function testControllerIndexAndNewRoutes(): void
    {
        $this->client->loginUser($this->adminA);

        // Index
        $crawler = $this->client->request('GET', '/admin/conges/');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1', 'Gestion des Congés');

        // New page
        $crawler = $this->client->request('GET', '/admin/conges/new');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1', 'Nouvelle demande de congé');
    }
}
