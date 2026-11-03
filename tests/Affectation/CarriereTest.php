<?php

namespace App\Tests\Affectation;

use App\Entity\Affectation;
use App\Entity\Employe;
use App\Entity\Fonction;
use App\Entity\Institution;
use App\Entity\Poste;
use App\Entity\Service;
use App\Entity\Structure;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Service\CarriereService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CarriereTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private CarriereService $carriereService;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $userA;
    private User $userB;
    private Structure $structureA1;
    private Structure $structureA2;
    private Structure $structureB;
    private Service $serviceA1;
    private Service $serviceA2;
    private Fonction $fonctionA;
    private Employe $employeA;
    private Employe $employeB;
    private Poste $posteA1;
    private Poste $posteA2;
    private Poste $posteB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->carriereService = $this->client->getContainer()->get(CarriereService::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        // 1. Tenants
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Carriere Alpha ' . uniqid());
        $this->tenantA->setCode('TCA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Carriere Beta ' . uniqid());
        $this->tenantB->setCode('TCB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Users
        $this->userA = $this->createUser('admin_carriere_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->userB = $this->createUser('admin_carriere_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 3. Structures & Services
        $this->structureA1 = new Structure();
        $this->structureA1->setNom('Direction Financière Alpha');
        $this->structureA1->setCode('DFA-' . uniqid());
        $this->structureA1->setType(Structure::TYPE_DIRECTION);
        $this->structureA1->setInstitution($this->tenantA);
        $this->em->persist($this->structureA1);

        $this->structureA2 = new Structure();
        $this->structureA2->setNom('Direction Ressources Humaines Alpha');
        $this->structureA2->setCode('DRHA-' . uniqid());
        $this->structureA2->setType(Structure::TYPE_DIRECTION);
        $this->structureA2->setInstitution($this->tenantA);
        $this->em->persist($this->structureA2);

        $this->structureB = new Structure();
        $this->structureB->setNom('Direction Beta');
        $this->structureB->setCode('DB-' . uniqid());
        $this->structureB->setType(Structure::TYPE_DIRECTION);
        $this->structureB->setInstitution($this->tenantB);
        $this->em->persist($this->structureB);

        $this->serviceA1 = new Service();
        $this->serviceA1->setNom('Service Comptabilité');
        $this->serviceA1->setInstitution($this->tenantA);
        $this->serviceA1->setStructure($this->structureA1);
        $this->em->persist($this->serviceA1);

        $this->serviceA2 = new Service();
        $this->serviceA2->setNom('Service Recrutement');
        $this->serviceA2->setInstitution($this->tenantA);
        $this->serviceA2->setStructure($this->structureA2);
        $this->em->persist($this->serviceA2);

        $this->fonctionA = new Fonction();
        $this->fonctionA->setLibelle('Analyste ' . uniqid());
        $this->em->persist($this->fonctionA);

        // 4. Postes
        $this->posteA1 = new Poste();
        $this->posteA1->setReference('PST-A1-' . uniqid());
        $this->posteA1->setIntitule('Comptable Principal');
        $this->posteA1->setStatut(Poste::STATUT_VACANT);
        $this->posteA1->setNiveau(Poste::NIVEAU_CADRE);
        $this->posteA1->setInstitution($this->tenantA);
        $this->posteA1->setStructure($this->structureA1);
        $this->posteA1->setService($this->serviceA1);
        $this->em->persist($this->posteA1);

        $this->posteA2 = new Poste();
        $this->posteA2->setReference('PST-A2-' . uniqid());
        $this->posteA2->setIntitule('Responsable Paie');
        $this->posteA2->setStatut(Poste::STATUT_VACANT);
        $this->posteA2->setNiveau(Poste::NIVEAU_CADRE);
        $this->posteA2->setInstitution($this->tenantA);
        $this->posteA2->setStructure($this->structureA2);
        $this->posteA2->setService($this->serviceA2);
        $this->em->persist($this->posteA2);

        $this->posteB = new Poste();
        $this->posteB->setReference('PST-B-' . uniqid());
        $this->posteB->setIntitule('Contrôleur Beta');
        $this->posteB->setStatut(Poste::STATUT_VACANT);
        $this->posteB->setNiveau(Poste::NIVEAU_CADRE);
        $this->posteB->setInstitution($this->tenantB);
        $this->posteB->setStructure($this->structureB);
        $this->em->persist($this->posteB);

        // 5. Employes
        $this->employeA = new Employe();
        $this->employeA->setNom('Nodji');
        $this->employeA->setPrenom('Blaise');
        $this->employeA->setEmail('blaise_' . uniqid() . '@alpha.com');
        $this->employeA->setMatricule('MAT-BLA-' . uniqid());
        $this->employeA->setInstitution($this->tenantA);
        $this->employeA->setUser($this->userA);
        $this->employeA->setActif(true);
        $this->employeA->setSituationAdministrative(Employe::SITUATION_EN_ACTIVITE);
        $this->employeA->setDateEmbauche(new \DateTime('2022-01-10'));
        $this->employeA->setDatePriseService(new \DateTime('2022-01-15'));
        $this->employeA->setSalaire(450000.00);
        $this->em->persist($this->employeA);

        $this->employeB = new Employe();
        $this->employeB->setNom('Koffi');
        $this->employeB->setPrenom('Serge');
        $this->employeB->setEmail('serge_' . uniqid() . '@beta.com');
        $this->employeB->setMatricule('MAT-KOF-' . uniqid());
        $this->employeB->setInstitution($this->tenantB);
        $this->employeB->setUser($this->userB);
        $this->employeB->setActif(true);
        $this->employeB->setSituationAdministrative(Employe::SITUATION_EN_ACTIVITE);
        $this->employeB->setDateEmbauche(new \DateTime('2022-01-10'));
        $this->employeB->setSalaire(450000.00);
        $this->em->persist($this->employeB);

        $this->em->flush();
    }

    private function createUser(string $email, array $roles, Institution $tenant): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setInstitution($tenant);
        $user->setPassword($this->hasher->hashPassword($user, 'Password123!'));
        $this->em->persist($user);
        return $user;
    }

    public function testAffectationIndexDisplaysKpisAndFilters(): void
    {
        $this->client->loginUser($this->userA);
        $crawler = $this->client->request('GET', '/admin/affectation');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Carrière & Mouvements du Personnel');
        $this->assertSelectorExists('table.table');
    }

    public function testWorkflowCompletPropositionValidationPriseService(): void
    {
        // 1. Création d'une proposition de mouvement
        $affectation = $this->carriereService->creerMouvement(
            employe: $this->employeA,
            typeMouvement: Affectation::TYPE_MUTATION,
            dateEffet: new \DateTime('+10 days'),
            structure: $this->structureA1,
            service: $this->serviceA1,
            poste: $this->posteA1,
            fonction: $this->fonctionA,
            dateDecision: new \DateTime(),
            referenceActe: 'ARR-2026-MUT-01',
            motif: 'Mutation pour convenance de service',
            autoValiderEtActiver: false
        );

        $this->assertEquals(Affectation::STATUT_PROPOSEE, $affectation->getStatut());
        // L'agent n'a pas encore changé de poste ni de structure
        $this->assertNull($this->employeA->getPoste());
        $this->assertEquals(Poste::STATUT_VACANT, $this->posteA1->getStatut());

        // 2. Validation du mouvement via interface HTTP
        $this->client->loginUser($this->userA);
        $crawler = $this->client->request('GET', '/admin/affectation/' . $affectation->getId());
        $this->assertResponseIsSuccessful();
        $token = $crawler->filter('form[action$="/valider"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/affectation/' . $affectation->getId() . '/valider', [
            '_token' => $token,
        ]);
        $this->assertResponseRedirects('/admin/affectation/' . $affectation->getId());

        $this->em->clear();
        /** @var Affectation $affectation */
        $affectation = $this->em->getRepository(Affectation::class)->find($affectation->getId());
        $this->assertEquals(Affectation::STATUT_VALIDEE, $affectation->getStatut());

        // 3. Confirmation de prise de service
        $crawlerPrise = $this->client->request('GET', '/admin/affectation/' . $affectation->getId() . '/prise-service');
        $this->assertResponseIsSuccessful();
        $tokenPrise = $crawlerPrise->filter('form input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/affectation/' . $affectation->getId() . '/prise-service', [
            '_token' => $tokenPrise,
            'date_prise_service' => (new \DateTime())->format('Y-m-d'),
        ]);
        $this->assertResponseRedirects('/admin/affectation/' . $affectation->getId());

        $this->em->clear();
        $affectation = $this->em->getRepository(Affectation::class)->find($affectation->getId());
        $employe = $this->em->getRepository(Employe::class)->find($this->employeA->getId());
        $poste = $this->em->getRepository(Poste::class)->find($this->posteA1->getId());

        $this->assertEquals(Affectation::STATUT_ACTIVE, $affectation->getStatut());
        $this->assertNotNull($employe->getPoste());
        $this->assertEquals($poste->getId(), $employe->getPoste()->getId());
        $this->assertEquals($this->structureA1->getId(), $employe->getStructure()->getId());
        $this->assertEquals(Poste::STATUT_OCCUPE, $poste->getStatut());
        $this->assertNotNull($poste->getOccupantActuel());
        $this->assertEquals($employe->getId(), $poste->getOccupantActuel()->getId());
    }

    public function testMutationInterServicesClosesPreviousAffectationAndFreesOldPoste(): void
    {
        // 1. Première affectation sur Poste A1
        $aff1 = $this->carriereService->creerMouvement(
            employe: $this->employeA,
            typeMouvement: Affectation::TYPE_AFFECTATION_INITIALE,
            dateEffet: new \DateTime('-6 months'),
            poste: $this->posteA1,
            autoValiderEtActiver: true
        );

        $this->em->refresh($this->employeA);
        $this->em->refresh($this->posteA1);
        $this->assertEquals(Affectation::STATUT_ACTIVE, $aff1->getStatut());
        $this->assertEquals($this->posteA1->getId(), $this->employeA->getPoste()->getId());
        $this->assertEquals(Poste::STATUT_OCCUPE, $this->posteA1->getStatut());

        // 2. Mutation de l'agent sur Poste A2
        $aff2 = $this->carriereService->creerMouvement(
            employe: $this->employeA,
            typeMouvement: Affectation::TYPE_MUTATION,
            dateEffet: new \DateTime(),
            poste: $this->posteA2,
            autoValiderEtActiver: true
        );

        $this->em->refresh($aff1);
        $this->em->refresh($aff2);
        $this->em->refresh($this->employeA);
        $this->em->refresh($this->posteA1);
        $this->em->refresh($this->posteA2);

        // L'ancienne affectation doit être CLOTUREE
        $this->assertEquals(Affectation::STATUT_CLOTUREE, $aff1->getStatut());
        $this->assertNotNull($aff1->getDateFin());

        // La nouvelle est ACTIVE
        $this->assertEquals(Affectation::STATUT_ACTIVE, $aff2->getStatut());

        // L'ancien poste A1 est désormais VACANT sans occupant
        $this->assertEquals(Poste::STATUT_VACANT, $this->posteA1->getStatut());
        $this->assertNull($this->posteA1->getOccupantActuel());

        // Le nouveau poste A2 est OCCUPE par employeA
        $this->assertEquals(Poste::STATUT_OCCUPE, $this->posteA2->getStatut());
        $this->assertEquals($this->employeA->getId(), $this->posteA2->getOccupantActuel()->getId());
        $this->assertEquals($this->posteA2->getId(), $this->employeA->getPoste()->getId());
    }

    public function testCessationActiviteFreesPosteAndUpdatesSituationAdmin(): void
    {
        // 1. Affecter d'abord l'agent à un poste
        $this->carriereService->creerMouvement(
            employe: $this->employeA,
            typeMouvement: Affectation::TYPE_AFFECTATION_INITIALE,
            dateEffet: new \DateTime('-1 year'),
            poste: $this->posteA1,
            autoValiderEtActiver: true
        );

        $this->em->refresh($this->employeA);
        $this->assertEquals($this->posteA1->getId(), $this->employeA->getPoste()->getId());

        // 2. Enregistrer une cessation d'activité pour retraite
        $cessation = $this->carriereService->enregistrerCessation(
            employe: $this->employeA,
            dateEffet: new \DateTime(),
            motif: 'Départ à la retraite anticipée',
            referenceActe: 'DEC-RET-2026-09',
            nouvelleSituationAdmin: Employe::SITUATION_RETRAITE
        );

        $this->em->refresh($cessation);
        $this->em->refresh($this->employeA);
        $this->em->refresh($this->posteA1);

        $this->assertEquals(Affectation::TYPE_CESSATION, $cessation->getTypeMouvement());
        $this->assertEquals(Affectation::STATUT_ACTIVE, $cessation->getStatut());
        $this->assertNull($this->employeA->getPoste());
        $this->assertNull($this->employeA->getStructure());
        $this->assertEquals(Employe::SITUATION_RETRAITE, $this->employeA->getSituationAdministrative());
        $this->assertEquals(Poste::STATUT_VACANT, $this->posteA1->getStatut());
    }

    public function testCarriereTimelineComplete(): void
    {
        $this->carriereService->creerMouvement(
            employe: $this->employeA,
            typeMouvement: Affectation::TYPE_AFFECTATION_INITIALE,
            dateEffet: new \DateTime('2022-01-15'),
            poste: $this->posteA1,
            autoValiderEtActiver: true
        );

        $this->carriereService->creerMouvement(
            employe: $this->employeA,
            typeMouvement: Affectation::TYPE_MUTATION,
            dateEffet: new \DateTime('2024-06-01'),
            poste: $this->posteA2,
            autoValiderEtActiver: true
        );

        $timeline = $this->carriereService->getCarriereTimeline($this->employeA);

        $this->assertGreaterThanOrEqual(3, count($timeline));
        // Le premier élément (recrutement) est présent
        $this->assertEquals('RECRUTEMENT', $timeline[0]['type']);
    }

    public function testMultiTenancyStrictIsolationOnAffectation(): void
    {
        // Créer une affectation sur Tenant A
        $affA = $this->carriereService->creerMouvement(
            employe: $this->employeA,
            typeMouvement: Affectation::TYPE_AFFECTATION_INITIALE,
            dateEffet: new \DateTime(),
            poste: $this->posteA1,
            autoValiderEtActiver: true
        );

        // User B (Tenant B) tente d'accéder à l'affectation du Tenant A
        $this->client->loginUser($this->userB);
        $this->client->request('GET', '/admin/affectation/' . $affA->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIdorProtectionCannotAssociateCrossTenantEntities(): void
    {
        $this->expectException(AccessDeniedException::class);

        // Tentative d'affecter employeA (Tenant A) à posteB (Tenant B)
        $this->carriereService->creerMouvement(
            employe: $this->employeA,
            typeMouvement: Affectation::TYPE_MUTATION,
            dateEffet: new \DateTime(),
            poste: $this->posteB,
            autoValiderEtActiver: false
        );
    }
}
