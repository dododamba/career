<?php

namespace App\Tests\Absence;

use App\Entity\Absence;
use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\TypeAbsence;
use App\Entity\TypeActe;
use App\Entity\TypeConges;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Repository\AbsenceRepository;
use App\Repository\CongesRepository;
use App\Repository\TypeAbsenceRepository;
use App\Repository\TypeCongesRepository;
use App\Security\Voter\AbsenceVoter;
use App\Service\AbsenceService;
use App\Service\CongeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AbsenceTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private AbsenceService $absenceService;
    private CongeService $congeService;
    private AbsenceRepository $absenceRepository;
    private TypeAbsenceRepository $typeAbsenceRepository;
    private CongesRepository $congesRepository;
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
    private TypeAbsence $typeExamen;
    private TypeAbsence $typeConvenance;
    private TypeAbsence $typeMedicale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->absenceService = $this->client->getContainer()->get(AbsenceService::class);
        $this->congeService = $this->client->getContainer()->get(CongeService::class);
        $this->absenceRepository = $this->client->getContainer()->get(AbsenceRepository::class);
        $this->typeAbsenceRepository = $this->client->getContainer()->get(TypeAbsenceRepository::class);
        $this->congesRepository = $this->client->getContainer()->get(CongesRepository::class);
        $this->typeCongesRepository = $this->client->getContainer()->get(TypeCongesRepository::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Tenants
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Absence Alpha ' . uniqid());
        $this->tenantA->setCode('TAA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Absence Beta ' . uniqid());
        $this->tenantB->setCode('TAB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Users
        $this->adminA = $this->createUser('admin_abs_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->managerA = $this->createUser('mgr_abs_a_' . uniqid() . '@alpha.com', ['ROLE_MANAGER'], $this->tenantA);
        $this->agentA = $this->createUser('agent_abs_a_' . uniqid() . '@alpha.com', ['ROLE_USER'], $this->tenantA);
        $this->adminB = $this->createUser('admin_abs_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 3. Employes
        $this->employeA = new Employe();
        $this->employeA->setNom('OUATTARA')
            ->setPrenom('Agent Alpha')
            ->setMatricule('MAT-ABSA-' . uniqid())
            ->setUser($this->agentA)
            ->setInstitution($this->tenantA)
            ->setSalaire(3200.0)
            ->setActif(true);
        $this->em->persist($this->employeA);

        $this->employeManager = new Employe();
        $this->employeManager->setNom('KONE')
            ->setPrenom('Manager Alpha')
            ->setMatricule('MAT-MGR-' . uniqid())
            ->setUser($this->managerA)
            ->setInstitution($this->tenantA)
            ->setSalaire(4800.0)
            ->setActif(true);
        $this->em->persist($this->employeManager);

        $this->employeB = new Employe();
        $this->employeB->setNom('TOURE')
            ->setPrenom('Agent Beta')
            ->setMatricule('MAT-ABSB-' . uniqid())
            ->setUser($this->adminB)
            ->setInstitution($this->tenantB)
            ->setSalaire(3500.0)
            ->setActif(true);
        $this->em->persist($this->employeB);

        // 4. Types d'absence
        $this->typeExamen = $this->typeAbsenceRepository->findByCode(TypeAbsence::CODE_CONCOURS_EXAMEN)
            ?? (new TypeAbsence())
                ->setCode(TypeAbsence::CODE_CONCOURS_EXAMEN)
                ->setLibelle('Concours ou examen professionnel')
                ->setUnite('JOURS')
                ->setDureeMax('5.00')
                ->setQuotaAnnuelMax('10.00')
                ->setJustificatifRequis(true)
                ->setRemuneree(true);
        if (!$this->typeExamen->getId()) {
            $this->em->persist($this->typeExamen);
        }

        $this->typeConvenance = $this->typeAbsenceRepository->findByCode(TypeAbsence::CODE_CONVENANCE_PERSONNELLE)
            ?? (new TypeAbsence())
                ->setCode(TypeAbsence::CODE_CONVENANCE_PERSONNELLE)
                ->setLibelle('Convenance personnelle')
                ->setUnite('JOURS')
                ->setDureeMax('3.00')
                ->setQuotaAnnuelMax('10.00')
                ->setJustificatifRequis(false)
                ->setRemuneree(false);
        if (!$this->typeConvenance->getId()) {
            $this->em->persist($this->typeConvenance);
        }

        $this->typeMedicale = $this->typeAbsenceRepository->findByCode(TypeAbsence::CODE_CONSULTATION_MEDICALE)
            ?? (new TypeAbsence())
                ->setCode(TypeAbsence::CODE_CONSULTATION_MEDICALE)
                ->setLibelle('Consultation médicale')
                ->setUnite('HEURES')
                ->setDureeMax('4.00')
                ->setQuotaAnnuelMax('20.00')
                ->setJustificatifRequis(true)
                ->setRemuneree(true);
        if (!$this->typeMedicale->getId()) {
            $this->em->persist($this->typeMedicale);
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

    private function createDocument(Employe $employe, string $titre = 'Convocation'): Document
    {
        $doc = new Document();
        $doc->setTitre($titre)
            ->setReference('REF-' . uniqid())
            ->setCategorie(Document::CATEGORIE_FORMATION)
            ->setFichierNomOriginal('justificatif.pdf')
            ->setFichierNomStockage('tenant_' . $employe->getInstitution()->getId() . '/justificatif.pdf')
            ->setTypeMime('application/pdf')
            ->setTaille(2048)
            ->setEmploye($employe)
            ->setInstitution($employe->getInstitution());

        $this->em->persist($doc);
        $this->em->flush();

        return $doc;
    }

    public function testDemandeAbsenceSuccess(): void
    {
        $debut = new \DateTimeImmutable('2026-11-10');
        $fin = new \DateTimeImmutable('2026-11-12');

        $absence = $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Raison personnelle de convenance',
            demandeur: $this->agentA
        );

        $this->assertNotNull($absence->getId());
        $this->assertSame(Absence::STATUT_EN_ATTENTE, $absence->getStatut());
        $this->assertEquals(3.00, (float) $absence->getDuree());
        $this->assertSame('JOURS', $absence->getUnite());
        $this->assertCount(1, $absence->getAbsenceLogs());
        $this->assertSame('create', $absence->getAbsenceLogs()->first()->getAction());
    }

    public function testDemandeAbsenceInvalideDates(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('La date de début ne peut pas être postérieure à la date de fin.');

        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-11-15'),
            dateFin: new \DateTimeImmutable('2026-11-10'),
            motif: 'Dates inversées'
        );
    }

    public function testJustificatifObligatoire(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Un document justificatif est obligatoire');

        // typeExamen exige un justificatif
        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeExamen,
            dateDebut: new \DateTimeImmutable('2026-11-02'),
            dateFin: new \DateTimeImmutable('2026-11-03'),
            motif: 'Concours sans pièce justificative',
            document: null
        );
    }

    public function testDemandeAvecJustificatif(): void
    {
        $justificatif = $this->createDocument($this->employeA, 'Convocation Concours ENA');

        $absence = $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeExamen,
            dateDebut: new \DateTimeImmutable('2026-11-02'),
            dateFin: new \DateTimeImmutable('2026-11-04'),
            motif: 'Concours administratif d\'entrée',
            document: $justificatif,
            demandeur: $this->agentA
        );

        $this->assertNotNull($absence->getId());
        $this->assertSame($justificatif->getId(), $absence->getDocument()->getId());
        $this->assertEquals(3.00, (float) $absence->getDuree());
    }

    public function testDureeMaxDepassee(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('dépasse la durée maximale autorisée');

        // typeConvenance max is 3 days, here requesting 5 days
        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-11-10'),
            dateFin: new \DateTimeImmutable('2026-11-14'),
            motif: 'Demande trop longue'
        );
    }

    public function testQuotaAnnuelDepasse(): void
    {
        // 1. First absence of 3 days
        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-03-01'),
            dateFin: new \DateTimeImmutable('2026-03-03'),
            motif: 'Première absence'
        );

        // 2. Second absence of 3 days
        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-04-01'),
            dateFin: new \DateTimeImmutable('2026-04-03'),
            motif: 'Deuxième absence'
        );

        // 3. Third absence of 3 days (total now 9 days, quota max is 10)
        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-05-01'),
            dateFin: new \DateTimeImmutable('2026-05-03'),
            motif: 'Troisième absence'
        );

        // 4. Fourth absence of 2 days (9 + 2 = 11 > 10 quota max)
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Le quota annuel');

        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-06-01'),
            dateFin: new \DateTimeImmutable('2026-06-02'),
            motif: 'Quatrième absence dépassant le quota'
        );
    }

    public function testAntiOverlapWithExistingAbsence(): void
    {
        // Absence du 10 au 12
        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-11-10'),
            dateFin: new \DateTimeImmutable('2026-11-12'),
            motif: 'Première absence'
        );

        // Nouvelle absence chevauchante (11 au 13)
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('a déjà une demande d\'absence ou de congé');

        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-11-11'),
            dateFin: new \DateTimeImmutable('2026-11-13'),
            motif: 'Absence chevauchante'
        );
    }

    public function testAntiOverlapCrossCongeAndAbsence(): void
    {
        // 1. Création d'un congé du 1er au 10 décembre 2026
        $typeAnnuel = $this->typeCongesRepository->findOneBy(['code' => TypeConges::CODE_ANNUEL])
            ?? $this->typeCongesRepository->findOneBy([]);

        $conge = $this->congeService->demanderConge(
            employe: $this->employeA,
            typeConge: $typeAnnuel,
            dateDebut: new \DateTimeImmutable('2026-12-01'),
            dateFin: new \DateTimeImmutable('2026-12-10'),
            motif: 'Congé annuel fin d\'année',
            demandeur: $this->agentA
        );

        $this->assertNotNull($conge->getId());

        // 2. Tentative de demander une absence pendant le congé (5 au 6 décembre)
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('a déjà une demande d\'absence ou de congé');

        $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-12-05'),
            dateFin: new \DateTimeImmutable('2026-12-06'),
            motif: 'Absence en plein congé'
        );
    }

    public function testApprobationAbsenceGeneratesActe(): void
    {
        $absence = $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-11-20'),
            dateFin: new \DateTimeImmutable('2026-11-22'),
            motif: 'Absence à approuver',
            demandeur: $this->agentA
        );

        $this->assertSame(Absence::STATUT_EN_ATTENTE, $absence->getStatut());

        $absence = $this->absenceService->approuverAbsence(
            absence: $absence,
            approbateur: $this->managerA,
            genererActe: true,
            commentaire: 'Autorisation accordée'
        );

        $this->assertSame(Absence::STATUT_APPROUVE, $absence->getStatut());
        $this->assertNotNull($absence->getApprouvePar());
        $this->assertSame($this->managerA->getId(), $absence->getApprouvePar()->getId());
        $this->assertNotNull($absence->getDateDecision());

        // Acte officiel généré
        $this->assertNotNull($absence->getActe());
        $this->assertSame(Acte::STATUT_SIGNE, $absence->getActe()->getStatut());
        $this->assertStringContainsString('AA-', $absence->getActe()->getNumero());

        // AbsenceLog
        $lastLog = $absence->getAbsenceLogs()->last();
        $this->assertSame('approve', $lastLog->getAction());
    }

    public function testAntiSelfApproval(): void
    {
        $absence = $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-11-25'),
            dateFin: new \DateTimeImmutable('2026-11-26'),
            motif: 'Auto-approbation test',
            demandeur: $this->agentA
        );

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas approuver votre propre demande');

        // Agent A essaie d'approuver sa propre demande
        $this->absenceService->approuverAbsence(
            absence: $absence,
            approbateur: $this->agentA
        );
    }

    public function testRejetAbsence(): void
    {
        $absence = $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-11-27'),
            dateFin: new \DateTimeImmutable('2026-11-28'),
            motif: 'Absence à refuser',
            demandeur: $this->agentA
        );

        $absence = $this->absenceService->rejeterAbsence(
            absence: $absence,
            refuseur: $this->managerA,
            motifRefus: 'Nécessité absolue de service sur cette période'
        );

        $this->assertSame(Absence::STATUT_REFUSE, $absence->getStatut());
        $this->assertSame('Nécessité absolue de service sur cette période', $absence->getCommentaireDecision());
        $this->assertSame('reject', $absence->getAbsenceLogs()->last()->getAction());
    }

    public function testAnnulationAbsenceAbrogeActe(): void
    {
        $absence = $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-12-15'),
            dateFin: new \DateTimeImmutable('2026-12-16'),
            motif: 'Absence à annuler',
            demandeur: $this->agentA
        );

        $absence = $this->absenceService->approuverAbsence($absence, $this->managerA, true);
        $acte = $absence->getActe();
        $this->assertNotNull($acte);
        $this->assertSame(Acte::STATUT_SIGNE, $acte->getStatut());

        // Annulation
        $absence = $this->absenceService->annulerAbsence($absence, $this->managerA, 'Annulé pour empêchement');

        $this->assertSame(Absence::STATUT_ANNULE, $absence->getStatut());
        $this->assertSame(Acte::STATUT_ABROGE, $acte->getStatut());
    }

    public function testMultiTenantIsolation(): void
    {
        // Absence créée dans Tenant A
        $absenceA = $this->absenceService->demanderAbsence(
            employe: $this->employeA,
            typeAbsence: $this->typeConvenance,
            dateDebut: new \DateTimeImmutable('2026-10-15'),
            dateFin: new \DateTimeImmutable('2026-10-16'),
            motif: 'Absence Tenant A',
            demandeur: $this->agentA
        );

        // Voter test : l'admin du Tenant B ne doit pas avoir accès
        $voter = new AbsenceVoter($this->client->getContainer()->get('security.helper'));
        $tokenB = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $this->adminB,
            'main',
            $this->adminB->getRoles()
        );

        $this->assertFalse(
            $this->client->getContainer()->get('security.authorization_checker')->isGranted(AbsenceVoter::VIEW, $absenceA),
            'Sans token approprié l\'accès doit être refusé'
        );

        // Repository filtré : avec tenant filter activé, Tenant B ne voit pas l'absence de Tenant A
        $this->em->getFilters()->enable('tenant_filter')->setParameter('tenant_id', (string) $this->tenantB->getId());

        $absencesTenantB = $this->absenceRepository->findAll();
        foreach ($absencesTenantB as $abs) {
            $this->assertNotSame($absenceA->getId(), $abs->getId(), 'Absence de Tenant A ne doit pas fuiter dans Tenant B');
        }

        $this->em->getFilters()->disable('tenant_filter');
    }

    public function testControllerWebAccess(): void
    {
        $this->client->loginUser($this->adminA);

        // 1. Liste des absences
        $this->client->request('GET', '/admin/absences/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Autorisations d\'absence');

        // 2. Page de création
        $this->client->request('GET', '/admin/absences/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Nouvelle demande');
    }
}
