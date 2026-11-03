<?php

namespace App\Tests\Document;

use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Structure;
use App\Entity\User;
use App\MultiTenancy\TenantInterface;
use App\Service\DocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DocumentTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private DocumentService $documentService;

    private Institution $tenantA;
    private Institution $tenantB;
    private User $adminA;
    private User $userA;
    private User $userB;
    private Employe $employeA;
    private Employe $employeB;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $this->documentService = $this->client->getContainer()->get(DocumentService::class);

        if ($this->em->getFilters()->isEnabled('tenant_filter')) {
            $this->em->getFilters()->disable('tenant_filter');
        }

        $this->tempDir = sys_get_temp_dir() . '/primatech_test_docs_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

        // 1. Tenants
        $this->tenantA = new Institution();
        $this->tenantA->setNom('Tenant Doc Alpha ' . uniqid());
        $this->tenantA->setCode('TDA-' . uniqid());
        $this->tenantA->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantA);

        $this->tenantB = new Institution();
        $this->tenantB->setNom('Tenant Doc Beta ' . uniqid());
        $this->tenantB->setCode('TDB-' . uniqid());
        $this->tenantB->setStatus(TenantInterface::STATUS_ACTIVE);
        $this->em->persist($this->tenantB);

        // 2. Users
        $this->adminA = $this->createUser('admin_doc_a_' . uniqid() . '@alpha.com', ['ROLE_ADMIN'], $this->tenantA);
        $this->userA = $this->createUser('agent_doc_a_' . uniqid() . '@alpha.com', ['ROLE_USER'], $this->tenantA);
        $this->userB = $this->createUser('agent_doc_b_' . uniqid() . '@beta.com', ['ROLE_ADMIN'], $this->tenantB);

        // 3. Employes
        $this->employeA = new Employe();
        $this->employeA->setNom('Ouedraogo');
        $this->employeA->setPrenom('Issa');
        $this->employeA->setEmail('issa_' . uniqid() . '@alpha.com');
        $this->employeA->setMatricule('MAT-DOC-A-' . uniqid());
        $this->employeA->setInstitution($this->tenantA);
        $this->employeA->setUser($this->userA);
        $this->employeA->setActif(true);
        $this->employeA->setSituationAdministrative(Employe::SITUATION_EN_ACTIVITE);
        $this->employeA->setDateEmbauche(new \DateTime('2023-01-01'));
        $this->employeA->setSalaire(450000.00);
        $this->em->persist($this->employeA);

        $this->employeB = new Employe();
        $this->employeB->setNom('Traore');
        $this->employeB->setPrenom('Moussa');
        $this->employeB->setEmail('moussa_' . uniqid() . '@beta.com');
        $this->employeB->setMatricule('MAT-DOC-B-' . uniqid());
        $this->employeB->setInstitution($this->tenantB);
        $this->employeB->setUser($this->userB);
        $this->employeB->setActif(true);
        $this->employeB->setSituationAdministrative(Employe::SITUATION_EN_ACTIVITE);
        $this->employeB->setDateEmbauche(new \DateTime('2023-01-01'));
        $this->employeB->setSalaire(450000.00);
        $this->em->persist($this->employeB);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Nettoyage fichiers temporaires
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
            @rmdir($this->tempDir);
        }
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

    private function createDummyUploadedFile(string $filename, string $content, string $mimeType = 'application/pdf'): UploadedFile
    {
        $path = $this->tempDir . '/' . $filename;
        file_put_contents($path, $content);

        return new UploadedFile($path, $filename, $mimeType, null, true);
    }

    public function testCoffreFortIndexDisplaysKpisAndFilters(): void
    {
        $this->client->loginUser($this->adminA);
        $crawler = $this->client->request('GET', '/admin/document');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Coffre-fort & Documents RH');
        $this->assertSelectorExists('table.table');
    }

    public function testUploadDocumentWithIntegrityHashAndInitialVersion(): void
    {
        $file = $this->createDummyUploadedFile('contrat_travail.pdf', '%PDF-1.4 dummy pdf content for unit test');

        $document = $this->documentService->uploadDocument(
            file: $file,
            institution: $this->tenantA,
            titre: 'Contrat de Travail Initial CDI',
            categorie: Document::CATEGORIE_CONTRAT,
            niveauConfidentialite: Document::CONFIDENTIALITE_CONFIDENTIEL,
            employe: $this->employeA,
            reference: 'CTR-2026-001',
            description: 'Contrat d\'engagement signé',
            dateDocument: new \DateTime('2023-01-05'),
            uploader: $this->adminA
        );

        $this->assertNotNull($document->getId());
        $this->assertEquals('Contrat de Travail Initial CDI', $document->getTitre());
        $this->assertEquals(Document::CATEGORIE_CONTRAT, $document->getCategorie());
        $this->assertEquals(1, $document->getNumeroDerniereVersion());
        $this->assertCount(1, $document->getVersions());
        $this->assertNotNull($document->getFichierHash());
        $this->assertEquals(64, strlen($document->getFichierHash())); // SHA-256 length

        // Vérification de la présence physique du fichier hors racine web
        $filePath = $this->documentService->getDocumentFilePath($document);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('tenant_' . $this->tenantA->getId(), $filePath);
    }

    public function testUploadNewVersionIncrementsVersionNumberAndPreservesHistory(): void
    {
        $fileV1 = $this->createDummyUploadedFile('arrete_v1.pdf', 'Contenu V1 initial');
        $document = $this->documentService->uploadDocument(
            file: $fileV1,
            institution: $this->tenantA,
            titre: 'Arrêté de nomination',
            categorie: Document::CATEGORIE_ACTE_ADMIN,
            employe: $this->employeA,
            uploader: $this->adminA
        );

        $hashV1 = $document->getFichierHash();
        $this->assertEquals(1, $document->getNumeroDerniereVersion());

        // Dépôt de la version 2
        $fileV2 = $this->createDummyUploadedFile('arrete_v2_signe.pdf', 'Contenu V2 modifie et signe');
        $v2 = $this->documentService->uploadNewVersion(
            document: $document,
            file: $fileV2,
            commentaireVersion: 'Ajout de la signature du ministre',
            uploader: $this->adminA
        );

        $this->em->refresh($document);
        $this->assertEquals(2, $v2->getNumeroVersion());
        $this->assertEquals(2, $document->getNumeroDerniereVersion());
        $this->assertCount(2, $document->getVersions());
        $this->assertNotEquals($hashV1, $document->getFichierHash());
    }

    public function testSecureStreamingDownloadAndPreviewHeaders(): void
    {
        $file = $this->createDummyUploadedFile('attestation.pdf', '%PDF-1.4 Attestation Content');
        $document = $this->documentService->uploadDocument(
            file: $file,
            institution: $this->tenantA,
            titre: 'Attestation de travail',
            categorie: Document::CATEGORIE_AUTRE,
            employe: $this->employeA,
            uploader: $this->adminA
        );

        $this->client->loginUser($this->adminA);

        // 1. Test téléchargement (attachment)
        $this->client->request('GET', '/admin/document/' . $document->getId() . '/telecharger');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
        $disposition = $this->client->getResponse()->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment;', $disposition);
        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');

        // 2. Test prévisualisation (inline)
        $this->client->request('GET', '/admin/document/' . $document->getId() . '/previsualiser');
        $this->assertResponseIsSuccessful();
        $dispositionInline = $this->client->getResponse()->headers->get('Content-Disposition');
        $this->assertStringContainsString('inline;', $dispositionInline);
    }

    public function testDocumentAttachedToEmployeAppearsInDossier(): void
    {
        $file = $this->createDummyUploadedFile('diplome_master.pdf', '%PDF-1.4 Diplome Master');
        $doc = $this->documentService->uploadDocument(
            file: $file,
            institution: $this->tenantA,
            titre: 'Diplôme de Master en Informatique',
            categorie: Document::CATEGORIE_DIPLOME_CV,
            employe: $this->employeA,
            uploader: $this->adminA
        );

        $this->em->clear();
        $this->client->loginUser($this->adminA);
        $this->client->request('GET', '/admin/employe/' . $this->employeA->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#tab-documents', 'Diplôme de Master en Informatique');
    }

    public function testMultiTenancyStrictIsolationOnDocuments(): void
    {
        $file = $this->createDummyUploadedFile('secret_alpha.pdf', 'Secret document alpha');
        $docA = $this->documentService->uploadDocument(
            file: $file,
            institution: $this->tenantA,
            titre: 'Note confidentielle Alpha',
            categorie: Document::CATEGORIE_AUTRE,
            uploader: $this->adminA
        );

        // User B (Tenant B) tente d'accéder au document de Tenant A
        $this->client->loginUser($this->userB);

        // Fiche
        $this->client->request('GET', '/admin/document/' . $docA->getId());
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [403, 404], true));

        // Téléchargement
        $this->client->request('GET', '/admin/document/' . $docA->getId() . '/telecharger');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [403, 404], true));

        // Prévisualisation
        $this->client->request('GET', '/admin/document/' . $docA->getId() . '/previsualiser');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [403, 404], true));
    }

    public function testIdorProtectionCannotAttachCrossTenantEntities(): void
    {
        $this->expectException(AccessDeniedException::class);

        $file = $this->createDummyUploadedFile('cross_tenant.pdf', 'Cross tenant attack');

        // Tentative d'attacher employeB (Tenant B) à un document créé pour tenantA
        $this->documentService->uploadDocument(
            file: $file,
            institution: $this->tenantA,
            titre: 'Attaque IDOR',
            categorie: Document::CATEGORIE_AUTRE,
            employe: $this->employeB,
            uploader: $this->adminA
        );
    }

    public function testConfidentialityRestrictionsForNonAdmin(): void
    {
        $file = $this->createDummyUploadedFile('dossier_disciplinaire.pdf', 'Sanction disciplinaire secrete');
        $docSecret = $this->documentService->uploadDocument(
            file: $file,
            institution: $this->tenantA,
            titre: 'Enquête disciplinaire confidentielle',
            categorie: Document::CATEGORIE_DISCIPLINE,
            niveauConfidentialite: Document::CONFIDENTIALITE_SECRET_RH,
            employe: $this->employeA,
            uploader: $this->adminA
        );

        // Agent A (simple ROLE_USER du même tenant A) ne doit pas pouvoir télécharger une pièce SECRET_RH
        $this->client->loginUser($this->userA);
        $this->client->request('GET', '/admin/document/' . $docSecret->getId() . '/telecharger');

        $this->assertResponseStatusCodeSame(403);
    }
}
