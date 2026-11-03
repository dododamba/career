<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Document;
use App\Entity\DocumentVersion;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\User;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DocumentService
{
    private const ALLOWED_MIME_TYPES = [
        // PDF
        'application/pdf',
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        // Microsoft Office
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        // OpenOffice / LibreOffice
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        // Texte
        'text/plain',
        'text/csv',
    ];

    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25 Mo

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentRepository $documentRepository,
        #[Autowire('%documents_storage_directory%')]
        private string $storageBaseDirectory
    ) {
    }

    /**
     * Upload sécurisé d'un document RH avec isolation tenant et versionnement initial v1.
     */
    public function uploadDocument(
        UploadedFile $file,
        Institution $institution,
        string $titre,
        string $categorie,
        string $niveauConfidentialite = Document::CONFIDENTIALITE_CONFIDENTIEL,
        ?Employe $employe = null,
        ?Affectation $affectation = null,
        ?string $reference = null,
        ?string $description = null,
        ?\DateTimeInterface $dateDocument = null,
        ?User $uploader = null,
        string $statut = Document::STATUT_VALIDE
    ): Document {
        // 1. Contrôle d'isolation multi-tenant stricte
        if ($employe && $employe->getInstitution() && $employe->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('L\'agent rattaché appartient à une autre institution.');
        }
        if ($affectation && $affectation->getInstitution() && $affectation->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('L\'affectation rattachée appartient à une autre institution.');
        }

        // 2. Validation du fichier
        $this->validateUploadedFile($file);

        // 3. Calcul du hash d'intégrité SHA-256
        $fileHash = hash_file('sha256', $file->getRealPath());
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->guessExtension() ?: pathinfo($originalFilename, PATHINFO_EXTENSION);
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';

        // 4. Stockage physique sécurisé hors du web root
        $tenantStorageDir = $this->getTenantStorageDirectory($institution);
        $storageFilename = sprintf('%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(16)), strtolower($extension));

        try {
            $file->move($tenantStorageDir, $storageFilename);
        } catch (FileException $e) {
            throw new \RuntimeException('Erreur lors du déplacement physique du fichier : ' . $e->getMessage(), 0, $e);
        }

        // 5. Référence automatique si absente
        if (!$reference) {
            $prefix = match ($categorie) {
                Document::CATEGORIE_ACTE_ADMIN => 'DOC-ACT',
                Document::CATEGORIE_CONTRAT => 'DOC-CTR',
                Document::CATEGORIE_IDENTITE => 'DOC-IDN',
                Document::CATEGORIE_AFFECTATION => 'DOC-AFF',
                default => 'DOC-RH',
            };
            $reference = sprintf('%s-%s-%s', $prefix, date('Ymd'), strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)));
        }

        // 6. Création de l'entité Document
        $document = new Document();
        $document->setInstitution($institution);
        $document->setEmploye($employe);
        $document->setAffectation($affectation);
        $document->setAjoutePar($uploader);
        $document->setTitre($titre);
        $document->setReference($reference);
        $document->setCategorie($categorie);
        $document->setNiveauConfidentialite($niveauConfidentialite);
        $document->setStatut($statut);
        $document->setDescription($description);
        $document->setDateDocument($dateDocument ?? new \DateTime());
        $document->setFichierNomOriginal($originalFilename);
        $document->setFichierNomStockage($storageFilename);
        $document->setTypeMime($mimeType);
        $document->setTaille($fileSize);
        $document->setFichierHash($fileHash);

        // 7. Création de la version initiale v1
        $version = new DocumentVersion();
        $version->setDocument($document);
        $version->setInstitution($institution);
        $version->setCreePar($uploader);
        $version->setNumeroVersion(1);
        $version->setFichierNomOriginal($originalFilename);
        $version->setFichierNomStockage($storageFilename);
        $version->setTypeMime($mimeType);
        $version->setTaille($fileSize);
        $version->setFichierHash($fileHash);
        $version->setCommentaireVersion('Dépôt initial du document');

        $document->addVersion($version);

        $this->entityManager->persist($document);
        $this->entityManager->persist($version);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * Dépôt d'une nouvelle version d'un document existant.
     */
    public function uploadNewVersion(
        Document $document,
        UploadedFile $file,
        ?string $commentaireVersion = null,
        ?User $uploader = null
    ): DocumentVersion {
        $institution = $document->getInstitution();
        if (!$institution) {
            throw new \LogicException('Document sans institution.');
        }

        $this->validateUploadedFile($file);

        $fileHash = hash_file('sha256', $file->getRealPath());
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->guessExtension() ?: pathinfo($originalFilename, PATHINFO_EXTENSION);
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';

        $tenantStorageDir = $this->getTenantStorageDirectory($institution);
        $storageFilename = sprintf('%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(16)), strtolower($extension));

        try {
            $file->move($tenantStorageDir, $storageFilename);
        } catch (FileException $e) {
            throw new \RuntimeException('Erreur lors du stockage de la nouvelle version : ' . $e->getMessage(), 0, $e);
        }

        $nextVersionNum = $document->getNumeroDerniereVersion() + 1;

        $version = new DocumentVersion();
        $version->setDocument($document);
        $version->setInstitution($institution);
        $version->setCreePar($uploader);
        $version->setNumeroVersion($nextVersionNum);
        $version->setFichierNomOriginal($originalFilename);
        $version->setFichierNomStockage($storageFilename);
        $version->setTypeMime($mimeType);
        $version->setTaille($fileSize);
        $version->setFichierHash($fileHash);
        $version->setCommentaireVersion($commentaireVersion ?? sprintf('Mise à jour version %d', $nextVersionNum));

        // Mettre à jour les métadonnées courantes du document
        $document->setFichierNomOriginal($originalFilename);
        $document->setFichierNomStockage($storageFilename);
        $document->setTypeMime($mimeType);
        $document->setTaille($fileSize);
        $document->setFichierHash($fileHash);
        $document->setUpdatedAt(new \DateTime());

        $document->addVersion($version);

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        return $version;
    }

    /**
     * Récupère le chemin absolu sur disque du fichier associé au document ou à sa version.
     */
    public function getDocumentFilePath(Document|DocumentVersion $target): string
    {
        $institution = $target->getInstitution();
        if (!$institution) {
            throw new NotFoundHttpException('Institution introuvable pour ce document.');
        }

        $storageFilename = $target->getFichierNomStockage();
        if (!$storageFilename) {
            throw new NotFoundHttpException('Aucun fichier physique enregistré.');
        }

        $filePath = $this->getTenantStorageDirectory($institution) . DIRECTORY_SEPARATOR . $storageFilename;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException(sprintf('Le fichier physique "%s" est introuvable sur le serveur de stockage.', $storageFilename));
        }

        return $filePath;
    }

    /**
     * Génère une réponse HTTP BinaryFileResponse sécurisée (streaming hors web root).
     */
    public function serveDocument(Document|DocumentVersion $target, bool $asAttachment = true): BinaryFileResponse
    {
        $filePath = $this->getDocumentFilePath($target);
        $originalFilename = $target->getFichierNomOriginal() ?? 'document';

        $disposition = $asAttachment
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition($disposition, $originalFilename);
        $response->headers->set('Content-Type', $target->getTypeMime() ?? 'application/octet-stream');
        $response->headers->set('Cache-Control', 'private, no-transform');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    /**
     * Supprime un document et l'ensemble de ses fichiers physiques sur disque.
     */
    public function supprimerDocument(Document $document): void
    {
        $institution = $document->getInstitution();
        if ($institution) {
            $storageDir = $this->getTenantStorageDirectory($institution);
            // Supprimer le fichier courant
            if ($document->getFichierNomStockage()) {
                $p = $storageDir . DIRECTORY_SEPARATOR . $document->getFichierNomStockage();
                if (file_exists($p)) {
                    @unlink($p);
                }
            }
            // Supprimer tous les fichiers de versions
            foreach ($document->getVersions() as $v) {
                if ($v->getFichierNomStockage()) {
                    $p = $storageDir . DIRECTORY_SEPARATOR . $v->getFichierNomStockage();
                    if (file_exists($p)) {
                        @unlink($p);
                    }
                }
            }
        }

        $this->entityManager->remove($document);
        $this->entityManager->flush();
    }

    /**
     * Répertoire de stockage sécurisé dédié à l'institution.
     */
    public function getTenantStorageDirectory(Institution $institution): string
    {
        $dir = rtrim($this->storageBaseDirectory, '/\\') . DIRECTORY_SEPARATOR . 'tenant_' . $institution->getId();
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        return $dir;
    }

    /**
     * Valide les contraintes de taille et type de fichier.
     */
    private function validateUploadedFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(sprintf('Le fichier dépasse la taille maximale autorisée de %d Mo.', self::MAX_FILE_SIZE / 1024 / 1024));
        }

        $mime = $file->getMimeType();
        if ($mime && !in_array(strtolower($mime), self::ALLOWED_MIME_TYPES, true)) {
            // Tolérance si extension bureautique courante
            $ext = strtolower($file->guessExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'txt', 'csv'];
            if (!in_array($ext, $allowedExts, true)) {
                throw new \InvalidArgumentException(sprintf('Format de fichier non autorisé (%s). Les formats acceptés sont PDF, Images, Word, Excel et Texte.', $mime));
            }
        }
    }
}
