<?php

namespace App\Tests\Acte;

use App\Entity\Acte;
use App\Entity\Affectation;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Structure;
use App\Entity\TypeActe;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Repository\ActeRepository;
use App\Repository\TypeActeRepository;
use App\Service\ActeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActeTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private ActeService $acteService;
    private ActeRepository $acteRepository;
    private TypeActeRepository $typeActeRepository;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $adminA;
    private User $userA;
    private User $userB;
    private Employe $employeA;
    private Employe $employeB;
    private Structure $structureA;
    private TypeActe $typeArrete;
    private TypeActe $typeDecision;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->acteService = $this->client->getContainer()->get(ActeService::class);
        $this->acteRepository = $this->client->getContainer()->get(ActeRepository::class);
        $this->typeActeRepository = $this->client->getContainer()->get(TypeActeRepository::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Tenants
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Acte Alpha ' . uniqid());
        $this->tenantA->setCode('TAA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Acte Beta ' . uniqid());
        $this->tenantB->setCode('TAB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Users
        $this->adminA = $this->createUser('admin_acte_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->userA = $this->createUser('agent_acte_a_' . uniqid() . '@alpha.com', ['ROLE_USER'], $this->tenantA);
        $this->userB = $this->createUser('agent_acte_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 3. Structure
        $this->structureA = new Structure();
        $this->structureA->setNom('Direction des Ressources Humaines ' . uniqid());
        $this->structureA->setCode('DRH-' . uniqid());
        $this->structureA->setType(Structure::TYPE_DIRECTION);
        $this->structureA->setInstitution($this->tenantA);
        $this->structureA->setStatut(Structure::STATUT_ACTIVE);
        $this->em->persist($this->structureA);

        // 4. Employes
        $this->employeA = new Employe();
        $this->employeA->setNom('Kaboré');
        $this->employeA->setPrenom('Paul');
        $this->employeA->setEmail('paul_' . uniqid() . '@alpha.com');
        $this->employeA->setMatricule('MAT-ACT-A-' . uniqid());
        $this->employeA->setInstitution($this->tenantA);
        $this->employeA->setUser($this->userA);
        $this->employeA->setStructure($this->structureA);
        $this->employeA->setActif(true);
        $this->employeA->setSituationAdministrative(Employe::SITUATION_EN_ACTIVITE);
        $this->employeA->setDateEmbauche(new \DateTime('2024-01-15'));
        $this->employeA->setSalaire(450000.00);
        $this->em->persist($this->employeA);

        $this->employeB = new Employe();
        $this->employeB->setNom('Sangaré');
        $this->employeB->setPrenom('Ali');
        $this->employeB->setEmail('ali_' . uniqid() . '@beta.com');
        $this->employeB->setMatricule('MAT-ACT-B-' . uniqid());
        $this->employeB->setInstitution($this->tenantB);
        $this->employeB->setUser($this->userB);
        $this->employeB->setActif(true);
        $this->employeB->setSituationAdministrative(Employe::SITUATION_EN_ACTIVITE);
        $this->employeB->setDateEmbauche(new \DateTime('2024-01-15'));
        $this->employeB->setSalaire(450000.00);
        $this->em->persist($this->employeB);

        // 5. Types d'actes (standards ou récupérés)
        $this->typeArrete = $this->typeActeRepository->findByCode(TypeActe::CODE_ARRETE);
        if (!$this->typeArrete) {
            $this->typeArrete = new TypeActe();
            $this->typeArrete->setCode(TypeActe::CODE_ARRETE);
            $this->typeArrete->setLibelle('Arrêté');
            $this->typeArrete->setActif(true);
            $this->em->persist($this->typeArrete);
        }

        $this->typeDecision = $this->typeActeRepository->findByCode(TypeActe::CODE_DECISION);
        if (!$this->typeDecision) {
            $this->typeDecision = new TypeActe();
            $this->typeDecision->setCode(TypeActe::CODE_DECISION);
            $this->typeDecision->setLibelle('Décision');
            $this->typeDecision->setActif(true);
            $this->em->persist($this->typeDecision);
        }

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function createUser(string $email, array $roles, Institution $institution): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setInstitution($institution);
        $user->setPassword($this->hasher->hashPassword($user, 'Secret123!'));
        $this->em->persist($user);

        return $user;
    }

    /**
     * Test 1 : Création d'un acte via le service métier ActeService.
     */
    public function testCreerActeService(): void
    {
        $numero = 'ARR-2026-001-' . uniqid();
        $dateSig = new \DateTime('2026-03-01');
        $dateEffet = new \DateTime('2026-04-01');

        $acte = $this->acteService->creerActe(
            institution: $this->tenantA,
            typeActe: $this->typeArrete,
            numero: $numero,
            dateSignature: $dateSig,
            dateEffet: $dateEffet,
            objet: 'Nomination de M. Paul Kaboré',
            signataireQualite: 'Le Ministre',
            signataireNom: 'Dr. Zongo',
            statut: Acte::STATUT_SIGNE,
            employe: $this->employeA,
            currentUser: $this->adminA
        );

        $this->assertNotNull($acte->getId());
        $this->assertEquals($numero, $acte->getNumero());
        $this->assertEquals(Acte::STATUT_SIGNE, $acte->getStatut());
        $this->assertEquals('success', $acte->getStatutBadgeClass());
        $this->assertEquals($this->tenantA->getId(), $acte->getInstitution()->getId());
        $this->assertEquals($this->employeA->getId(), $acte->getEmploye()->getId());

        // Recherche via repository
        $found = $this->acteRepository->findByNumero($numero, $this->tenantA);
        $this->assertNotNull($found);
        $this->assertEquals('Nomination de M. Paul Kaboré', $found->getObjet());
    }

    /**
     * Test 2 : Cycle de vie juridique et transitions de statuts.
     */
    public function testCycleDeVieStatuts(): void
    {
        $acte = $this->acteService->creerActe(
            institution: $this->tenantA,
            typeActe: $this->typeDecision,
            numero: 'DEC-2026-TEST-' . uniqid(),
            dateSignature: new \DateTime(),
            dateEffet: new \DateTime(),
            objet: 'Projet de réorganisation',
            signataireQualite: 'Le Directeur Général',
            statut: Acte::STATUT_PROJET,
            currentUser: $this->adminA
        );

        $this->assertEquals(Acte::STATUT_PROJET, $acte->getStatut());

        // 1. Signature
        $this->acteService->changerStatut($acte, Acte::STATUT_SIGNE);
        $this->assertEquals(Acte::STATUT_SIGNE, $acte->getStatut());

        // 2. Notification / Publication
        $this->acteService->changerStatut($acte, Acte::STATUT_NOTIFIE);
        $this->assertEquals(Acte::STATUT_NOTIFIE, $acte->getStatut());
        $this->assertNotNull($acte->getDatePublication());

        // 3. Abrogation
        $this->acteService->changerStatut($acte, Acte::STATUT_ABROGE);
        $this->assertEquals(Acte::STATUT_ABROGE, $acte->getStatut());

        // 4. Statut invalide -> Exception
        $this->expectException(\InvalidArgumentException::class);
        $this->acteService->changerStatut($acte, 'STATUT_INEXISTANT');
    }

    /**
     * Test 3 : Articulation avec la Carrière (Module 06) et déductions automatiques.
     */
    public function testLiaisonMouvementCarriere(): void
    {
        $affectation = new Affectation();
        $affectation->setReference('AFF-' . uniqid());
        $affectation->setInstitution($this->tenantA);
        $affectation->setEmploye($this->employeA);
        $affectation->setStructure($this->structureA);
        $affectation->setTypeMouvement(Affectation::TYPE_MUTATION);
        $affectation->setDatePriseService(new \DateTime('2026-05-01'));
        $affectation->setDateEffet(new \DateTime('2026-05-01'));
        $affectation->setStatut(Affectation::STATUT_VALIDEE);
        $this->em->persist($affectation);
        $this->em->flush();

        $acte = $this->acteService->creerActe(
            institution: $this->tenantA,
            typeActe: $this->typeArrete,
            numero: 'ARR-CARRIERE-' . uniqid(),
            dateSignature: new \DateTime(),
            dateEffet: new \DateTime('2026-05-01'),
            objet: 'Arrêté portant mutation de M. Paul Kaboré',
            signataireQualite: 'Le Ministre',
            affectation: $affectation,
            currentUser: $this->adminA
        );

        $this->assertNotNull($acte->getAffectation());
        $this->assertEquals($affectation->getId(), $acte->getAffectation()->getId());
        // Déduction automatique de l'agent et de la structure à partir de l'affectation
        $this->assertEquals($this->employeA->getId(), $acte->getEmploye()->getId());
        $this->assertEquals($this->structureA->getId(), $acte->getStructure()->getId());

        $actesAffectation = $this->acteRepository->findByAffectation($affectation);
        $this->assertCount(1, $actesAffectation);
        $this->assertEquals($acte->getId(), $actesAffectation[0]->getId());
    }

    /**
     * Test 4 : Articulation avec le Coffre-fort documentaire (Module 07).
     */
    public function testAttacherEtDissocierDocument(): void
    {
        $document = new Document();
        $document->setInstitution($this->tenantA);
        $document->setTitre('Scan officiel signé de l\'arrêté');
        $document->setFichierNomStockage('tenant_' . $this->tenantA->getId() . '/test_scan.pdf');
        $document->setFichierNomOriginal('arrete_signe.pdf');
        $document->setTypeMime('application/pdf');
        $document->setTaille(102400);
        $document->setFichierHash(hash('sha256', 'contenu_test_arrete'));
        $document->setStatut(Document::STATUT_VALIDE);
        $document->setCategorie(Document::CATEGORIE_ACTE_ADMIN);
        $this->em->persist($document);
        $this->em->flush();

        $acte = $this->acteService->creerActe(
            institution: $this->tenantA,
            typeActe: $this->typeArrete,
            numero: 'ARR-DOC-' . uniqid(),
            dateSignature: new \DateTime(),
            dateEffet: new \DateTime(),
            objet: 'Arrêté avec scan joint',
            signataireQualite: 'Le Recteur',
            document: $document,
            currentUser: $this->adminA
        );

        $this->assertNotNull($acte->getDocument());
        $this->assertEquals($document->getId(), $acte->getDocument()->getId());

        // Dissociation
        $this->acteService->dissocierDocument($acte);
        $this->assertNull($acte->getDocument());
    }

    /**
     * Test 5 : Filiation juridique entre actes (Acte parent, modifications et abrogations).
     */
    public function testFiliationActeParentEtModificatif(): void
    {
        // Acte initial
        $acteInitial = $this->acteService->creerActe(
            institution: $this->tenantA,
            typeActe: $this->typeArrete,
            numero: 'ARR-INIT-' . uniqid(),
            dateSignature: new \DateTime('2025-01-01'),
            dateEffet: new \DateTime('2025-01-01'),
            objet: 'Nomination initiale',
            signataireQualite: 'Le Ministre',
            statut: Acte::STATUT_SIGNE,
            currentUser: $this->adminA
        );

        $this->assertEquals(Acte::STATUT_SIGNE, $acteInitial->getStatut());

        // Acte modificatif ultérieur
        $acteModif = $this->acteService->creerActe(
            institution: $this->tenantA,
            typeActe: $this->typeArrete,
            numero: 'ARR-MODIF-' . uniqid(),
            dateSignature: new \DateTime('2026-02-01'),
            dateEffet: new \DateTime('2026-02-01'),
            objet: 'Arrêté modificatif portant changement de fonction',
            signataireQualite: 'Le Ministre',
            acteParent: $acteInitial,
            currentUser: $this->adminA
        );

        $this->assertNotNull($acteModif->getActeParent());
        $this->assertEquals($acteInitial->getId(), $acteModif->getActeParent()->getId());
        // L'acte initial passe automatiquement en statut MODIFIE
        $this->assertEquals(Acte::STATUT_MODIFIE, $acteInitial->getStatut());
        $this->assertTrue($acteInitial->getActesModificatifs()->contains($acteModif));
    }

    /**
     * Test 6 : Cloisonnement strict multi-tenant et protection anti-IDOR.
     */
    public function testAntiIdorCrossTenantProtection(): void
    {
        // 1. Tentative de créer un acte dans Tenant A en référençant un agent de Tenant B -> AccessDeniedException
        $this->expectException(AccessDeniedException::class);
        $this->acteService->creerActe(
            institution: $this->tenantA,
            typeActe: $this->typeArrete,
            numero: 'ARR-ILLEGAL-' . uniqid(),
            dateSignature: new \DateTime(),
            dateEffet: new \DateTime(),
            objet: 'Tentative d\'atteinte cross-tenant',
            signataireQualite: 'Inconnu',
            employe: $this->employeB // Agent du Tenant B !
        );
    }

    /**
     * Test 7 : Consultation Web HTTP du registre et Fiche 360° avec isolation Voter / Tenant.
     */
    public function testHttpRegistryAndShowAccess(): void
    {
        $acte = $this->acteService->creerActe(
            institution: $this->tenantA,
            typeActe: $this->typeArrete,
            numero: 'ARR-HTTP-VIEW-' . uniqid(),
            dateSignature: new \DateTime(),
            dateEffet: new \DateTime(),
            objet: 'Acte pour test HTTP',
            signataireQualite: 'Le Secrétaire Général',
            employe: $this->employeA,
            currentUser: $this->adminA
        );

        // 1. Admin A accède au registre
        $this->client->loginUser($this->adminA);
        $this->client->request('GET', '/admin/acte');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Registre des Actes Administratifs');
        $this->assertSelectorTextContains('table', $acte->getNumero());

        // 2. Admin A accède à la fiche 360° de l'acte
        $this->client->request('GET', '/admin/acte/' . $acte->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', $acte->getNumero());
        $this->assertSelectorTextContains('body', 'Le Secrétaire Général');

        // 3. User B (autre tenant) tente d'accéder à l'acte de Tenant A -> Fail-closed 403 ou 404
        $this->client->loginUser($this->userB);
        $this->client->request('GET', '/admin/acte/' . $acte->getId());
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [403, 404], true),
            sprintf('L\'accès cross-tenant à l\'acte doit renvoyer 403 ou 404, reçu : %d', $statusCode)
        );
    }

    /**
     * Test 8 : Création via formulaire Web HTTP et mise à jour de statut avec token CSRF.
     */
    public function testHttpCreerEtModifierStatutForm(): void
    {
        $this->client->loginUser($this->adminA);

        // 1. Accès au formulaire de création
        $crawler = $this->client->request('GET', '/admin/acte/nouveau');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Enregistrer un Acte Administratif');

        // 2. Soumission du formulaire
        $numeroForm = 'ARR-FORM-' . uniqid();
        $form = $crawler->selectButton('Enregistrer l\'acte officiel')->form([
            'acte[type_acte]' => $this->typeArrete->getId(),
            'acte[numero]' => $numeroForm,
            'acte[date_signature]' => '2026-06-15',
            'acte[date_effet]' => '2026-07-01',
            'acte[statut]' => Acte::STATUT_SIGNE,
            'acte[objet]' => 'Arrêté d\'avancement d\'échelon',
            'acte[signataire_qualite]' => 'Le Ministre Délégué',
            'acte[signataire_nom]' => 'M. Sissoko',
            'acte[employe]' => $this->employeA->getId(),
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'enregistré avec succès');

        $acteCree = $this->acteRepository->findByNumero($numeroForm, $this->tenantA);
        $this->assertNotNull($acteCree);
        $this->assertEquals('Arrêté d\'avancement d\'échelon', $acteCree->getObjet());

        // 3. Changement de statut vers NOTIFIE via l'action dédiée
        $showCrawler = $this->client->request('GET', '/admin/acte/' . $acteCree->getId());
        $this->assertResponseIsSuccessful();

        $token = $showCrawler->filter('form[action*="/statut"] input[name="_token"]')->first()->attr('value');
        $this->client->request('POST', '/admin/acte/' . $acteCree->getId() . '/statut', [
            '_token' => $token,
            'statut' => Acte::STATUT_NOTIFIE,
        ]);

        $this->assertResponseRedirects('/admin/acte/' . $acteCree->getId());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Notifié / Publié');

        $this->em->refresh($acteCree);
        $this->assertEquals(Acte::STATUT_NOTIFIE, $acteCree->getStatut());
    }
}
