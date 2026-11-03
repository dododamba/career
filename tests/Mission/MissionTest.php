<?php

namespace App\Tests\Mission;

use App\Entity\Absence;
use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Mission;
use App\Entity\MissionParticipant;
use App\Entity\TypeAbsence;
use App\Entity\TypeActe;
use App\Entity\TypeConges;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Repository\AbsenceRepository;
use App\Repository\CongesRepository;
use App\Repository\MissionRepository;
use App\Repository\TypeAbsenceRepository;
use App\Repository\TypeCongesRepository;
use App\Security\Voter\MissionVoter;
use App\Service\AbsenceService;
use App\Service\CongeService;
use App\Service\MissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MissionTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private MissionService $missionService;
    private MissionRepository $missionRepository;
    private AbsenceService $absenceService;
    private CongeService $congeService;
    private TypeAbsenceRepository $typeAbsenceRepository;
    private TypeCongesRepository $typeCongesRepository;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $adminA;
    private User $managerA;
    private User $agentA;
    private User $expertA;
    private User $adminB;
    private Employe $employeA;
    private Employe $employeManager;
    private Employe $employeExpert;
    private Employe $employeB;
    private TypeActe $typeOrdreMission;
    private TypeConges $typeCongeAnnuel;
    private TypeAbsence $typeAbsenceConvenance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->missionService = $this->client->getContainer()->get(MissionService::class);
        $this->missionRepository = $this->client->getContainer()->get(MissionRepository::class);
        $this->absenceService = $this->client->getContainer()->get(AbsenceService::class);
        $this->congeService = $this->client->getContainer()->get(CongeService::class);
        $this->typeAbsenceRepository = $this->client->getContainer()->get(TypeAbsenceRepository::class);
        $this->typeCongesRepository = $this->client->getContainer()->get(TypeCongesRepository::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Tenants
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Mission Alpha ' . uniqid());
        $this->tenantA->setCode('TMA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Mission Beta ' . uniqid());
        $this->tenantB->setCode('TMB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Users
        $this->adminA = $this->createUser('admin_mis_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->managerA = $this->createUser('mgr_mis_a_' . uniqid() . '@alpha.com', ['ROLE_MANAGER'], $this->tenantA);
        $this->agentA = $this->createUser('agent_mis_a_' . uniqid() . '@alpha.com', ['ROLE_USER'], $this->tenantA);
        $this->expertA = $this->createUser('expert_mis_a_' . uniqid() . '@alpha.com', ['ROLE_USER'], $this->tenantA);
        $this->adminB = $this->createUser('admin_mis_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 3. Employés
        $this->employeA = new Employe();
        $this->employeA->setNom('KOUAME')
            ->setPrenom('Agent Chef')
            ->setMatricule('MAT-MISA-' . uniqid())
            ->setUser($this->agentA)
            ->setInstitution($this->tenantA)
            ->setSalaire(3500.0)
            ->setActif(true);
        $this->em->persist($this->employeA);

        $this->employeManager = new Employe();
        $this->employeManager->setNom('TRAORE')
            ->setPrenom('Validateur RH')
            ->setMatricule('MAT-MGR-' . uniqid())
            ->setUser($this->managerA)
            ->setInstitution($this->tenantA)
            ->setSalaire(5000.0)
            ->setActif(true);
        $this->em->persist($this->employeManager);

        $this->employeExpert = new Employe();
        $this->employeExpert->setNom('BAMBA')
            ->setPrenom('Expert Métier')
            ->setMatricule('MAT-EXP-' . uniqid())
            ->setUser($this->expertA)
            ->setInstitution($this->tenantA)
            ->setSalaire(4200.0)
            ->setActif(true);
        $this->em->persist($this->employeExpert);

        $this->employeB = new Employe();
        $this->employeB->setNom('DIABATE')
            ->setPrenom('Agent Beta')
            ->setMatricule('MAT-MISB-' . uniqid())
            ->setUser($this->adminB)
            ->setInstitution($this->tenantB)
            ->setSalaire(3800.0)
            ->setActif(true);
        $this->em->persist($this->employeB);

        // 4. Types requis
        $this->typeOrdreMission = $this->em->getRepository(TypeActe::class)->findOneBy(['code' => TypeActe::CODE_ORDRE_MISSION])
            ?? (new TypeActe())
                ->setCode(TypeActe::CODE_ORDRE_MISSION)
                ->setLibelle('Ordre de mission')
                ->setActif(true);
        if (!$this->typeOrdreMission->getId()) {
            $this->em->persist($this->typeOrdreMission);
        }

        $this->typeCongeAnnuel = $this->typeCongesRepository->findOneBy(['code' => 'ANNUEL'])
            ?? $this->typeCongesRepository->findOneBy([])
            ?? (new TypeConges())
                ->setCode('ANNUEL')
                ->setNom('Congé Annuel')
                ->setDureeMax(30)
                ->setCumulable(false)
                ->setActif(true);
        if (!$this->typeCongeAnnuel->getId()) {
            $this->em->persist($this->typeCongeAnnuel);
        }

        $this->typeAbsenceConvenance = $this->typeAbsenceRepository->findByCode(TypeAbsence::CODE_CONVENANCE_PERSONNELLE)
            ?? (new TypeAbsence())
                ->setCode(TypeAbsence::CODE_CONVENANCE_PERSONNELLE)
                ->setLibelle('Convenance personnelle')
                ->setUnite('JOURS')
                ->setDureeMax('3.00')
                ->setQuotaAnnuelMax('10.00')
                ->setJustificatifRequis(false)
                ->setRemuneree(false);
        if (!$this->typeAbsenceConvenance->getId()) {
            $this->em->persist($this->typeAbsenceConvenance);
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

    public function testCreerMissionBrouillon(): void
    {
        $debut = new \DateTimeImmutable('2026-11-05');
        $fin = new \DateTimeImmutable('2026-11-08');

        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Audit de la direction régionale de Bouaké',
            destination: 'Bouaké',
            dateDebut: $debut,
            dateFin: $fin,
            description: 'Vérification comptable et conformité des procédures RH',
            pays: 'Côte d\'Ivoire',
            lieuPrecis: 'Direction Régionale Gbêkê',
            itineraire: 'Abidjan - Yamoussoukro - Bouaké',
            modeTransport: 'Véhicule de service',
            budgetEstime: '450000.00',
            imputationBudgetaire: 'LIGNE-702-DEP',
            priseEnCharge: 'ORGANISME_ACCUEIL',
            chefMission: $this->employeA,
            participantsData: [],
            soumettreDirectement: false,
            demandeur: $this->agentA
        );

        $this->assertNotNull($mission->getId());
        $this->assertSame(Mission::STATUT_BROUILLON, $mission->getStatut());
        $this->assertSame('Bouaké', $mission->getDestination());
        $this->assertSame(4, $mission->getDureeJours());
        $this->assertSame('450000.00', $mission->getBudgetEstime());
        $this->assertSame($this->employeA->getId(), $mission->getChefMission()?->getId());

        // Chef automatiquement ajouté dans les participants
        $this->assertCount(1, $mission->getParticipants());
        $this->assertSame(MissionParticipant::ROLE_CHEF_MISSION, $mission->getParticipants()->first()->getRole());

        // Vérification audit log
        $this->assertNotEmpty($mission->getMissionLogs());
        $this->assertSame('create', $mission->getMissionLogs()->first()->getAction());
    }

    public function testCreerMissionAvecParticipants(): void
    {
        $debut = new \DateTimeImmutable('2026-11-15');
        $fin = new \DateTimeImmutable('2026-11-18');

        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Formation technique régionale',
            destination: 'San-Pedro',
            dateDebut: $debut,
            dateFin: $fin,
            chefMission: $this->employeA,
            participantsData: [
                [
                    'employe' => $this->employeExpert,
                    'role' => MissionParticipant::ROLE_EXPERT,
                    'indemnitesMission' => '150000.00',
                    'fraisTransport' => '50000.00',
                    'observations' => 'Expert formateur principal',
                ],
            ],
            soumettreDirectement: true,
            demandeur: $this->agentA
        );

        $this->assertSame(Mission::STATUT_SOUMISE, $mission->getStatut());
        $this->assertCount(2, $mission->getParticipants());

        $expertParticipant = null;
        foreach ($mission->getParticipants() as $p) {
            if ($p->getEmploye()?->getId() === $this->employeExpert->getId()) {
                $expertParticipant = $p;
                break;
            }
        }

        $this->assertNotNull($expertParticipant);
        $this->assertSame(MissionParticipant::ROLE_EXPERT, $expertParticipant->getRole());
        $this->assertSame('150000.00', $expertParticipant->getIndemnitesMission());
        $this->assertSame('50000.00', $expertParticipant->getFraisTransport());
        $this->assertEquals(200000.0, (float) $expertParticipant->getTotalFrais());
    }

    public function testSoumettreMissionBrouillon(): void
    {
        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Visite de terrain',
            destination: 'Korhogo',
            dateDebut: new \DateTimeImmutable('2026-11-20'),
            dateFin: new \DateTimeImmutable('2026-11-22'),
            chefMission: $this->employeA,
            soumettreDirectement: false,
            demandeur: $this->agentA
        );

        $this->assertSame(Mission::STATUT_BROUILLON, $mission->getStatut());

        $mission = $this->missionService->soumettreMission($mission, $this->agentA);
        $this->assertSame(Mission::STATUT_SOUMISE, $mission->getStatut());
        $this->assertSame('submit', $mission->getMissionLogs()->last()->getAction());
    }

    public function testValiderMissionEtGenerationOrdreMission(): void
    {
        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Supervision des centres régionaux',
            destination: 'Yamoussoukro',
            dateDebut: new \DateTimeImmutable('2026-11-25'),
            dateFin: new \DateTimeImmutable('2026-11-27'),
            chefMission: $this->employeA,
            soumettreDirectement: true,
            demandeur: $this->agentA
        );

        // Validation par le Manager (qui ne fait pas partie de la mission)
        $validatedMission = $this->missionService->validerMission(
            mission: $mission,
            validateur: $this->managerA,
            genererOrdreMission: true,
            numeroOrdre: 'OM-2026-AUTO-01',
            commentaire: 'Mission autorisée au regard du programme trimestriel'
        );

        $this->assertSame(Mission::STATUT_VALIDEE, $validatedMission->getStatut());
        $this->assertNotNull($validatedMission->getDateValidation());
        $this->assertSame($this->managerA->getId(), $validatedMission->getValidePar()?->getId());

        // Vérification de la génération automatique de l'Ordre de Mission (Acte administratif)
        $acte = $validatedMission->getOrdreMission();
        $this->assertNotNull($acte, 'L\'ordre de mission officiel doit avoir été généré sous forme d\'Acte');
        $this->assertSame('OM-2026-AUTO-01', $acte->getNumero());
        $this->assertSame(Acte::STATUT_SIGNE, $acte->getStatut());
        $this->assertSame($this->employeA->getId(), $acte->getEmploye()?->getId());
        $this->assertStringContainsString('Yamoussoukro', $acte->getObjet());
    }

    public function testAntiAutoValidationChef(): void
    {
        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Mission auto-validée interdite',
            destination: 'Daloa',
            dateDebut: new \DateTimeImmutable('2026-12-01'),
            dateFin: new \DateTimeImmutable('2026-12-03'),
            chefMission: $this->employeA,
            soumettreDirectement: true,
            demandeur: $this->agentA
        );

        // Tentative de validation par le chef de mission lui-même
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas valider ou clôturer votre propre mission.');

        $this->missionService->validerMission(
            mission: $mission,
            validateur: $this->agentA
        );
    }

    public function testAntiAutoValidationParticipant(): void
    {
        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Mission participant validateur interdit',
            destination: 'Man',
            dateDebut: new \DateTimeImmutable('2026-12-05'),
            dateFin: new \DateTimeImmutable('2026-12-07'),
            chefMission: $this->employeA,
            participantsData: [
                ['employe' => $this->employeManager, 'role' => MissionParticipant::ROLE_PARTICIPANT],
            ],
            soumettreDirectement: true,
            demandeur: $this->agentA
        );

        // Le manager participe à la mission -> interdiction de valider
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas valider ou clôturer une mission dont vous faites partie.');

        $this->missionService->validerMission(
            mission: $mission,
            validateur: $this->managerA
        );
    }

    public function testChevauchementAvecConges(): void
    {
        // Créer un congé approuvé pour l'employeA
        $conge = new Conges();
        $conge->setEmploye($this->employeA)
            ->setTypeConge($this->typeCongeAnnuel)
            ->setDateDebut(new \DateTime('2026-12-10'))
            ->setDateFin(new \DateTime('2026-12-20'))
            ->setDateDiff(11)
            ->setStatut(Conges::STATUT_APPROUVE)
            ->setMotif('Congés annuels autorisés');
        $this->em->persist($conge);
        $this->em->flush();

        // Tentative de créer une mission sur la période de congé
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('L\'agent KOUAME Agent Chef est indisponible sur cette période : un congé est déjà programmé ou en attente.');

        $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Mission chevauchant un congé',
            destination: 'Bassam',
            dateDebut: new \DateTimeImmutable('2026-12-12'),
            dateFin: new \DateTimeImmutable('2026-12-15'),
            chefMission: $this->employeA,
            demandeur: $this->agentA
        );
    }

    public function testChevauchementAvecAbsence(): void
    {
        // Créer une autorisation d'absence approuvée pour l'employeA
        $absence = $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeAbsenceConvenance,
            dateDebut: new \DateTimeImmutable('2027-01-10'),
            dateFin: new \DateTimeImmutable('2027-01-12'),
            motif: 'Démarches personnelles',
            demandeur: $this->agentA
        );
        $this->absenceService->approuverAbsence($absence, $this->managerA, false);

        // Tentative de créer une mission sur la période d'absence
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('L\'agent KOUAME Agent Chef est indisponible sur cette période : une autorisation d\'absence est déjà enregistrée.');

        $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Mission chevauchant une absence',
            destination: 'Aboisso',
            dateDebut: new \DateTimeImmutable('2027-01-11'),
            dateFin: new \DateTimeImmutable('2027-01-14'),
            chefMission: $this->employeA,
            demandeur: $this->agentA
        );
    }

    public function testChevauchementDeuxMissionsSimultanees(): void
    {
        // Première mission validée
        $mission1 = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Première mission active',
            destination: 'Abengourou',
            dateDebut: new \DateTimeImmutable('2027-02-01'),
            dateFin: new \DateTimeImmutable('2027-02-05'),
            chefMission: $this->employeA,
            soumettreDirectement: true,
            demandeur: $this->agentA
        );
        $this->missionService->validerMission($mission1, $this->managerA);

        // Deuxième mission chevauchante
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('est déjà engagé sur une autre mission active sur cette période');

        $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Deuxième mission chevauchante',
            destination: 'Bondoukou',
            dateDebut: new \DateTimeImmutable('2027-02-03'),
            dateFin: new \DateTimeImmutable('2027-02-08'),
            chefMission: $this->employeA,
            demandeur: $this->agentA
        );
    }

    public function testCycleDeVieCompletMission(): void
    {
        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Inspection générale des sites',
            destination: 'Gagnoa',
            dateDebut: new \DateTimeImmutable('2027-03-01'),
            dateFin: new \DateTimeImmutable('2027-03-05'),
            chefMission: $this->employeA,
            soumettreDirectement: true,
            demandeur: $this->agentA
        );

        // 1. Validation
        $mission = $this->missionService->validerMission($mission, $this->managerA);
        $this->assertSame(Mission::STATUT_VALIDEE, $mission->getStatut());

        // 2. Démarrage
        $mission = $this->missionService->demarrerMission($mission, $this->agentA);
        $this->assertSame(Mission::STATUT_EN_COURS, $mission->getStatut());

        // 3. Fin de la mission
        $mission = $this->missionService->terminerMission($mission, $this->agentA);
        $this->assertSame(Mission::STATUT_TERMINEE, $mission->getStatut());

        // 4. Soumission du rapport de mission
        $mission = $this->missionService->soumettreRapport(
            mission: $mission,
            rapportTexte: 'Le rapport complet constate un taux de conformité de 94%. Les objectifs ont été atteints.',
            documentRapport: null,
            redacteur: $this->agentA
        );
        $this->assertNotNull($mission->getDateRapport());
        $this->assertStringContainsString('taux de conformité de 94%', $mission->getRapportTexte());

        // 5. Validation du rapport et clôture définitive
        $mission = $this->missionService->validerRapportEtCloturer(
            mission: $mission,
            validateur: $this->managerA,
            commentaire: 'Rapport examiné et approuvé par la direction.'
        );
        $this->assertSame(Mission::STATUT_CLOTUREE, $mission->getStatut());
        $this->assertSame('close', $mission->getMissionLogs()->last()->getAction());
    }

    public function testRejetMission(): void
    {
        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Mission non prioritaire',
            destination: 'Ferkessédougou',
            dateDebut: new \DateTimeImmutable('2027-04-01'),
            dateFin: new \DateTimeImmutable('2027-04-03'),
            chefMission: $this->employeA,
            soumettreDirectement: true,
            demandeur: $this->agentA
        );

        $mission = $this->missionService->rejeterMission(
            mission: $mission,
            refuseur: $this->managerA,
            motifRefus: 'Contraintes budgétaires strictes sur ce trimestre.'
        );

        $this->assertSame(Mission::STATUT_REFUSEE, $mission->getStatut());
        $this->assertSame('Contraintes budgétaires strictes sur ce trimestre.', $mission->getCommentaireValidation());
    }

    public function testAnnulationMissionAbrogeOrdreMission(): void
    {
        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Mission à annuler avec acte officiel',
            destination: 'Odienné',
            dateDebut: new \DateTimeImmutable('2027-05-10'),
            dateFin: new \DateTimeImmutable('2027-05-13'),
            chefMission: $this->employeA,
            soumettreDirectement: true,
            demandeur: $this->agentA
        );

        $mission = $this->missionService->validerMission($mission, $this->managerA, true, 'OM-2027-ODIENNE');
        $acte = $mission->getOrdreMission();
        $this->assertNotNull($acte);
        $this->assertSame(Acte::STATUT_SIGNE, $acte->getStatut());

        // Annulation de la mission
        $mission = $this->missionService->annulerMission(
            mission: $mission,
            utilisateur: $this->managerA,
            motif: 'Report des assises ministérielles à une date ultérieure.'
        );

        $this->assertSame(Mission::STATUT_ANNULEE, $mission->getStatut());
        $this->assertSame(Acte::STATUT_ABROGE, $acte->getStatut(), 'L\'ordre de mission doit être abrogé lors de l\'annulation');
    }

    public function testMultiTenantIsolation(): void
    {
        // Mission créée dans Tenant A
        $missionA = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Mission confidentielle Tenant A',
            destination: 'Grand-Bassam',
            dateDebut: new \DateTimeImmutable('2027-06-01'),
            dateFin: new \DateTimeImmutable('2027-06-03'),
            chefMission: $this->employeA,
            demandeur: $this->agentA
        );

        // 1. Isolation Voter
        $voter = new MissionVoter($this->client->getContainer()->get('security.helper'));
        $tokenB = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $this->adminB,
            'main',
            $this->adminB->getRoles()
        );

        // L'admin de Tenant B ne doit pas avoir accès
        $voteResult = $voter->vote($tokenB, $missionA, [MissionVoter::VIEW]);
        $this->assertSame(
            \Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_DENIED,
            $voteResult,
            'L\'administrateur du Tenant B ne doit pas pouvoir visualiser une mission du Tenant A'
        );

        // 2. Isolation Repository avec TenantFilter
        $this->em->getFilters()->enable('tenant_filter')->setParameter('tenant_id', (string) $this->tenantB->getId());

        $missionsTenantB = $this->missionRepository->findAll();
        foreach ($missionsTenantB as $m) {
            $this->assertNotSame($missionA->getId(), $m->getId(), 'La mission de Tenant A ne doit jamais apparaître pour Tenant B');
        }

        $this->em->getFilters()->disable('tenant_filter');
    }

    public function testControllerWebAccess(): void
    {
        // 1. Création de la mission préalable
        $mission = $this->missionService->creerMission(
            institution: $this->tenantA,
            objet: 'Mission test web controller',
            destination: 'Toumodi',
            dateDebut: new \DateTimeImmutable('2027-07-01'),
            dateFin: new \DateTimeImmutable('2027-07-03'),
            chefMission: $this->employeA,
            demandeur: $this->adminA
        );
        $missionId = $mission->getId();

        $this->client->loginUser($this->adminA);

        // 2. Liste des missions
        $this->client->request('GET', '/admin/missions/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Missions & Ordres de mission');

        // 3. Formulaire de nouvelle mission
        $this->client->request('GET', '/admin/missions/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Préparer une nouvelle mission');

        // 4. Fiche détaillée d'une mission
        $this->client->request('GET', '/admin/missions/' . $missionId);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mission');
        $this->assertSelectorTextContains('body', 'Toumodi');
    }
}
