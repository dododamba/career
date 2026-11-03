<?php

namespace App\Tests\Interim;

use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Interim;
use App\Entity\InterimLog;
use App\Entity\Poste;
use App\Entity\Structure;
use App\Entity\TypeActe;
use App\Entity\TypeConges;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Repository\InterimRepository;
use App\Repository\TypeActeRepository;
use App\Repository\TypeCongesRepository;
use App\Security\Voter\InterimVoter;
use App\Service\CongeService;
use App\Service\InterimService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class InterimTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private InterimService $interimService;
    private InterimRepository $interimRepository;
    private CongeService $congeService;
    private TypeCongesRepository $typeCongesRepository;
    private TypeActeRepository $typeActeRepository;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $adminA;
    private User $managerA;
    private User $titulaireUserA;
    private User $interimaireUserA;
    private User $adminB;
    private Employe $titulaireA;
    private Employe $interimaireA;
    private Employe $autreEmployeA;
    private Employe $employeB;
    private Structure $structureA;
    private Poste $posteA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->interimService = $this->client->getContainer()->get(InterimService::class);
        $this->interimRepository = $this->client->getContainer()->get(InterimRepository::class);
        $this->congeService = $this->client->getContainer()->get(CongeService::class);
        $this->typeCongesRepository = $this->client->getContainer()->get(TypeCongesRepository::class);
        $this->typeActeRepository = $this->client->getContainer()->get(TypeActeRepository::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Tenants
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Interim Alpha ' . uniqid());
        $this->tenantA->setCode('TIA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Interim Beta ' . uniqid());
        $this->tenantB->setCode('TIB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Structure & Poste
        $this->structureA = new Structure();
        $this->structureA->setNom('Direction des Affaires Juridiques ' . uniqid())
            ->setCode('DAJ-' . uniqid())
            ->setInstitution($this->tenantA);
        $this->em->persist($this->structureA);

        $this->posteA = new Poste();
        $this->posteA->setReference('PST-AJ-' . uniqid())
            ->setIntitule('Directeur des Affaires Juridiques ' . uniqid())
            ->setStructure($this->structureA)
            ->setInstitution($this->tenantA)
            ->setStatut(Poste::STATUT_OCCUPE);
        $this->em->persist($this->posteA);

        // 3. Users
        $this->adminA = $this->createUser('admin_int_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->managerA = $this->createUser('mgr_int_a_' . uniqid() . '@alpha.com', ['ROLE_MANAGER'], $this->tenantA);
        $this->titulaireUserA = $this->createUser('usr_tit_' . uniqid() . '@alpha.com', ['ROLE_USER'], $this->tenantA);
        $this->interimaireUserA = $this->createUser('usr_int_' . uniqid() . '@alpha.com', ['ROLE_USER'], $this->tenantA);
        $this->adminB = $this->createUser('admin_int_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 4. Employés
        $this->titulaireA = new Employe();
        $this->titulaireA->setNom('KOUADIO')
            ->setPrenom('Jean Titulaire')
            ->setMatricule('TIT-' . uniqid())
            ->setEmail('titulaire_' . uniqid() . '@alpha.com')
            ->setInstitution($this->tenantA)
            ->setStructure($this->structureA)
            ->setPosteActuel($this->posteA)
            ->setUser($this->titulaireUserA)
            ->setSalaire(500000.0)
            ->setActif(true);
        $this->em->persist($this->titulaireA);

        $this->interimaireA = new Employe();
        $this->interimaireA->setNom('KONE')
            ->setPrenom('Amadou Interimaire')
            ->setMatricule('INT-' . uniqid())
            ->setEmail('interimaire_' . uniqid() . '@alpha.com')
            ->setInstitution($this->tenantA)
            ->setStructure($this->structureA)
            ->setUser($this->interimaireUserA)
            ->setSalaire(450000.0)
            ->setActif(true);
        $this->em->persist($this->interimaireA);

        $this->autreEmployeA = new Employe();
        $this->autreEmployeA->setNom('TRAORE')
            ->setPrenom('Moussa Adjoint')
            ->setMatricule('ADJ-' . uniqid())
            ->setEmail('adjoint_' . uniqid() . '@alpha.com')
            ->setInstitution($this->tenantA)
            ->setUser($this->managerA)
            ->setSalaire(400000.0)
            ->setActif(true);
        $this->em->persist($this->autreEmployeA);

        $this->employeB = new Employe();
        $this->employeB->setNom('DIALLO')
            ->setPrenom('Mamadou Beta')
            ->setMatricule('BET-' . uniqid())
            ->setEmail('agent_b_' . uniqid() . '@beta.com')
            ->setInstitution($this->tenantB)
            ->setUser($this->adminB)
            ->setSalaire(420000.0)
            ->setActif(true);
        $this->em->persist($this->employeB);

        $this->em->flush();
    }

    private function createUser(string $email, array $roles, Institution $institution, ?Employe $employe = null): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setInstitution($institution);
        $user->setPassword($this->hasher->hashPassword($user, 'Password123!'));
        if ($employe) {
            $user->addEmploye($employe);
        }
        $this->em->persist($user);

        return $user;
    }

    public function testCreationInterimSuccess(): void
    {
        $debut = new \DateTime('+2 days');
        $fin = new \DateTime('+15 days');

        $interim = $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Départ en mission et suppléance de service',
            typeDelegation: Interim::TYPE_INTERIM_TOTAL,
            perimetre: [Interim::POUVOIR_TOUS, Interim::POUVOIR_VALIDATION_CONGES],
            poste: $this->posteA,
            structure: $this->structureA,
            soumettreDirectement: false,
            demandeur: $this->adminA
        );

        $this->em->flush();

        $this->assertNotNull($interim->getId());
        $this->assertSame(Interim::STATUT_BROUILLON, $interim->getStatut());
        $this->assertStringStartsWith('INT-', $interim->getReference());
        $this->assertSame($this->titulaireA->getId(), $interim->getTitulaire()->getId());
        $this->assertSame($this->interimaireA->getId(), $interim->getInterimaire()->getId());
        $this->assertSame($this->posteA->getId(), $interim->getPoste()->getId());
        $this->assertSame(14, $interim->getDureeEnJours()); // +2 to +15 is 13 or 14 days
        $this->assertTrue($interim->hasPouvoir(Interim::POUVOIR_TOUS));
        $this->assertTrue($interim->hasPouvoir(Interim::POUVOIR_VALIDATION_CONGES));

        // Vérifier logs d'audit
        $this->assertGreaterThanOrEqual(1, $interim->getInterimLogs()->count());
    }

    public function testCreationInterimValidationDatesInvalides(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('La date de début ne peut pas être postérieure à la date de fin.');

        $debut = new \DateTime('+10 days');
        $fin = new \DateTime('+2 days');

        $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Dates incohérentes'
        );
    }

    public function testCreationInterimMemePersonneInterdite(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('L\'intérimaire désigné doit être une personne distincte');

        $debut = new \DateTime('+1 day');
        $fin = new \DateTime('+5 days');

        $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->titulaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Auto-intérim'
        );
    }

    public function testMultiTenancyIsolationStrictCrossTenant(): void
    {
        $this->expectException(AccessDeniedException::class);

        $debut = new \DateTime('+1 day');
        $fin = new \DateTime('+5 days');

        // Tentative de désigner un intérimaire du Tenant B pour le titulaire du Tenant A
        $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->employeB,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Violation cross tenant'
        );
    }

    public function testDisponibiliteInterimaireCongeActif(): void
    {
        $debutConge = new \DateTime('+5 days');
        $finConge = new \DateTime('+12 days');

        // Créer un congé validé pour l'intérimaire
        $typeConge = $this->typeCongesRepository->findOneBy(['institution' => $this->tenantA]);
        if (!$typeConge) {
            $typeConge = new TypeConges();
            $typeConge->setLibelle('Congé Annuel ' . uniqid())
                ->setCode('CA-' . uniqid())
                ->setInstitution($this->tenantA);
            $this->em->persist($typeConge);
            $this->em->flush();
        }

        $conge = new Conges();
        $conge->setInstitution($this->tenantA)
            ->setEmploye($this->interimaireA)
            ->setTypeConge($typeConge)
            ->setDateDebut($debutConge)
            ->setDateFin($finConge)
            ->setDateDiff(7)
            ->setStatut(Conges::STATUT_APPROUVE);
        $this->em->persist($conge);
        $this->em->flush();

        // Tentative de créer un intérim qui chevauche le congé de l'intérimaire
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('est indisponible : un congé est déjà programmé');

        $debutInterim = new \DateTime('+7 days');
        $finInterim = new \DateTime('+10 days');

        $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debutInterim,
            dateFin: $finInterim,
            motif: 'Test indisponibilité'
        );
    }

    public function testCycleDeVieCompletSoumissionValidationAvecActe(): void
    {
        $debut = new \DateTime('+1 day');
        $fin = new \DateTime('+10 days');

        // 1. Création Brouillon
        $interim = $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Déplacement professionnel du titulaire',
            demandeur: $this->adminA
        );
        $this->em->flush();
        $this->assertSame(Interim::STATUT_BROUILLON, $interim->getStatut());

        // 2. Soumission
        $interim = $this->interimService->soumettreInterim($interim, $this->adminA);
        $this->em->flush();
        $this->assertSame(Interim::STATUT_SOUMIS, $interim->getStatut());

        // 3. Validation hiérarchique avec génération automatique d'acte officiel
        $interim = $this->interimService->validerInterim(
            interim: $interim,
            validateur: $this->managerA,
            genererDecision: true,
            commentaire: 'Validé en comex'
        );
        $this->em->flush();

        $this->assertSame(Interim::STATUT_ACTIF, $interim->getStatut());
        $this->assertSame($this->managerA->getId(), $interim->getValidePar()->getId());
        $this->assertNotNull($interim->getDateValidation());
        $this->assertNotNull($interim->getActe());
        $this->assertStringContainsString('DEC-', $interim->getActe()->getNumero());
        $this->assertSame('SIGNE', $interim->getActe()->getStatut());
    }

    public function testSeparationDesFonctionsValidateurNePeutEtreLeTitulaire(): void
    {
        $debut = new \DateTime('+1 day');
        $fin = new \DateTime('+8 days');

        $interim = $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Test séparation',
            soumettreDirectement: true,
            demandeur: $this->adminA
        );
        $this->em->flush();

        // Le titulaire essaie de valider lui-même la désignation de son propre intérimaire
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas valider ou refuser un intérim vous concernant.');

        $this->interimService->validerInterim(
            interim: $interim,
            validateur: $this->titulaireUserA
        );
    }

    public function testSeparationDesFonctionsValidateurNePeutEtreLInterimaire(): void
    {
        $debut = new \DateTime('+1 day');
        $fin = new \DateTime('+8 days');

        $interim = $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Test auto-désignation',
            soumettreDirectement: true,
            demandeur: $this->adminA
        );
        $this->em->flush();

        // L'intérimaire essaie de valider sa propre nomination
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas valider ou refuser votre propre désignation comme intérimaire.');

        $this->interimService->validerInterim(
            interim: $interim,
            validateur: $this->interimaireUserA
        );
    }

    public function testCycleDeVieRefus(): void
    {
        $debut = new \DateTime('+1 day');
        $fin = new \DateTime('+8 days');

        $interim = $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Proposition à refuser',
            soumettreDirectement: true,
            demandeur: $this->adminA
        );
        $this->em->flush();

        $interim = $this->interimService->refuserInterim(
            interim: $interim,
            refuseur: $this->managerA,
            motifRefus: 'Incompatibilité avec le planning du service'
        );
        $this->em->flush();

        $this->assertSame(Interim::STATUT_REFUSE, $interim->getStatut());
        $this->assertStringContainsString('Incompatibilité', $interim->getMotifFin());
    }

    public function testCycleDeVieRevocation(): void
    {
        $debut = new \DateTime('+1 day');
        $fin = new \DateTime('+10 days');

        $interim = $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Interim pour révocation',
            soumettreDirectement: true,
            demandeur: $this->adminA
        );
        $interim = $this->interimService->validerInterim($interim, $this->managerA);
        $this->em->flush();

        $this->assertSame(Interim::STATUT_ACTIF, $interim->getStatut());

        // Révocation anticipée
        $interim = $this->interimService->revoquerInterim(
            interim: $interim,
            utilisateur: $this->managerA,
            motifRevoquation: 'Reprise de service anticipée du titulaire'
        );
        $this->em->flush();

        $this->assertSame(Interim::STATUT_REVOQUE, $interim->getStatut());
        $this->assertNotNull($interim->getDateFinEffective());
        $this->assertStringContainsString('Reprise de service anticipée', $interim->getMotifFin());
    }

    public function testExpirationAutomatiqueInterimEchu(): void
    {
        // Créer un intérim dont la date de fin est dans le passé
        $debut = new \DateTime('-20 days');
        $fin = new \DateTime('-5 days');

        $interim = new Interim();
        $interim->setInstitution($this->tenantA)
            ->setReference('INT-EXP-' . uniqid())
            ->setTitulaire($this->titulaireA)
            ->setInterimaire($this->interimaireA)
            ->setDateDebut($debut)
            ->setDateFin($fin)
            ->setMotif('Intérim à expirer')
            ->setStatut(Interim::STATUT_ACTIF)
            ->setCreatedAt(new \DateTimeImmutable('-20 days'))
            ->setUpdatedAt(new \DateTimeImmutable('-20 days'));

        $this->em->persist($interim);
        $this->em->flush();

        // Exécution de la détection d'expiration
        $nbExpires = $this->interimService->verifierEtMettreAJourExpirations(new \DateTime());

        $this->assertGreaterThanOrEqual(1, $nbExpires);

        $this->em->refresh($interim);
        $this->assertSame(Interim::STATUT_TERMINE, $interim->getStatut());
        $this->assertNotNull($interim->getDateFinEffective());
        $this->assertStringContainsString('Expiration automatique', $interim->getMotifFin());
    }

    public function testInterimVoterPermissions(): void
    {
        $voter = $this->client->getContainer()->get(InterimVoter::class);

        $debut = new \DateTime('+1 day');
        $fin = new \DateTime('+7 days');

        $interim = $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Test permissions voter',
            soumettreDirectement: false,
            demandeur: $this->adminA
        );
        $this->em->flush();

        // 1. View:
        $tokenTitulaire = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($this->titulaireUserA, 'main', $this->titulaireUserA->getRoles());
        $tokenInterimaire = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($this->interimaireUserA, 'main', $this->interimaireUserA->getRoles());
        $tokenAdminB = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($this->adminB, 'main', $this->adminB->getRoles());

        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_GRANTED, $voter->vote($tokenTitulaire, $interim, [InterimVoter::VIEW]));
        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_GRANTED, $voter->vote($tokenInterimaire, $interim, [InterimVoter::VIEW]));
        // Tenant B ne peut pas voir un interim de Tenant A
        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_DENIED, $voter->vote($tokenAdminB, $interim, [InterimVoter::VIEW]));

        // 2. Validate: ni titulaire ni intérimaire ne peuvent valider
        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_DENIED, $voter->vote($tokenTitulaire, $interim, [InterimVoter::VALIDATE]));
        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_DENIED, $voter->vote($tokenInterimaire, $interim, [InterimVoter::VALIDATE]));
    }

    public function testWebControllerRoutesAccess(): void
    {
        $debut = new \DateTime('+2 days');
        $fin = new \DateTime('+6 days');
        $interim = $this->interimService->creerInterim(
            institution: $this->tenantA,
            titulaire: $this->titulaireA,
            interimaire: $this->interimaireA,
            dateDebut: $debut,
            dateFin: $fin,
            motif: 'Test affichage web',
            demandeur: $this->adminA
        );
        $this->em->flush();
        $interimId = $interim->getId();
        $interimRef = $interim->getReference();

        $this->client->loginUser($this->adminA);

        // 1. Index
        $this->client->request('GET', '/admin/interims/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Intérim & Délégations');

        // 2. New
        $this->client->request('GET', '/admin/interims/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Désigner un intérimaire');

        // 3. Show
        $this->client->request('GET', '/admin/interims/' . $interimId);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Intérim ' . $interimRef);

        // 4. Edit
        $this->client->request('GET', '/admin/interims/' . $interimId . '/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier l\'intérim');
    }
}
