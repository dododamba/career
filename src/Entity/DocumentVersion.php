<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\DocumentVersionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentVersionRepository::class)]
#[ORM\Table(name: 'document_version')]
#[ORM\Index(columns: ['document_id'], name: 'idx_doc_version_document')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_doc_version_institution')]
#[ORM\Index(columns: ['numero_version'], name: 'idx_doc_version_numero')]
class DocumentVersion implements TenantAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Document $document = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $cree_par = null;

    #[ORM\Column]
    private int $numero_version = 1;

    #[ORM\Column(length: 255)]
    private ?string $fichier_nom_original = null;

    #[ORM\Column(length: 255)]
    private ?string $fichier_nom_stockage = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $fichier_hash = null;

    #[ORM\Column]
    private int $taille = 0;

    #[ORM\Column(length: 100)]
    private ?string $type_mime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire_version = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->numero_version = 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;
        return $this;
    }

    public function getInstitution(): ?Institution
    {
        return $this->institution;
    }

    public function setInstitution(?Institution $institution): static
    {
        $this->institution = $institution;
        return $this;
    }

    public function getCreePar(): ?User
    {
        return $this->cree_par;
    }

    public function setCreePar(?User $cree_par): static
    {
        $this->cree_par = $cree_par;
        return $this;
    }

    public function getNumeroVersion(): int
    {
        return $this->numero_version;
    }

    public function setNumeroVersion(int $numero_version): static
    {
        $this->numero_version = $numero_version;
        return $this;
    }

    public function getFichierNomOriginal(): ?string
    {
        return $this->fichier_nom_original;
    }

    public function setFichierNomOriginal(string $fichier_nom_original): static
    {
        $this->fichier_nom_original = $fichier_nom_original;
        return $this;
    }

    public function getFichierNomStockage(): ?string
    {
        return $this->fichier_nom_stockage;
    }

    public function setFichierNomStockage(string $fichier_nom_stockage): static
    {
        $this->fichier_nom_stockage = $fichier_nom_stockage;
        return $this;
    }

    public function getFichierHash(): ?string
    {
        return $this->fichier_hash;
    }

    public function setFichierHash(?string $fichier_hash): static
    {
        $this->fichier_hash = $fichier_hash;
        return $this;
    }

    public function getTaille(): int
    {
        return $this->taille;
    }

    public function setTaille(int $taille): static
    {
        $this->taille = $taille;
        return $this;
    }

    public function getTypeMime(): ?string
    {
        return $this->type_mime;
    }

    public function setTypeMime(string $type_mime): static
    {
        $this->type_mime = $type_mime;
        return $this;
    }

    public function getCommentaireVersion(): ?string
    {
        return $this->commentaire_version;
    }

    public function setCommentaireVersion(?string $commentaire_version): static
    {
        $this->commentaire_version = $commentaire_version;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTailleFormatee(): string
    {
        $bytes = $this->taille;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' Go';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' Mo';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' Ko';
        }
        return $bytes . ' octets';
    }
}
