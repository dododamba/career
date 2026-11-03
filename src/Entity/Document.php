<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'document')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_document_institution')]
#[ORM\Index(columns: ['employe_id'], name: 'idx_document_employe')]
#[ORM\Index(columns: ['affectation_id'], name: 'idx_document_affectation')]
#[ORM\Index(columns: ['categorie'], name: 'idx_document_categorie')]
#[ORM\Index(columns: ['statut'], name: 'idx_document_statut')]
#[ORM\Index(columns: ['niveau_confidentialite'], name: 'idx_document_confidentialite')]
class Document implements TenantAwareInterface
{
    // Catégories de documents RH
    public const CATEGORIE_IDENTITE = 'IDENTITE';                 // CNI, Passeport, Acte de naissance
    public const CATEGORIE_CONTRAT = 'CONTRAT';                   // Contrat de travail, Lettre d'engagement, Avenant
    public const CATEGORIE_DIPLOME_CV = 'DIPLOME_CV';             // Diplômes, Certifications, CV
    public const CATEGORIE_ACTE_ADMIN = 'ACTE_ADMIN';             // Arrêté de nomination, Décret, Décision
    public const CATEGORIE_AFFECTATION = 'AFFECTATION';           // PV de prise de service, Note de mutation
    public const CATEGORIE_EVALUATION = 'EVALUATION';             // Fiche d'évaluation, Rapport de notation
    public const CATEGORIE_MEDICAL = 'MEDICAL';                   // Certificat médical, Aptitude
    public const CATEGORIE_DISCIPLINE = 'DISCIPLINE';             // Avertissement, Blâme, Sanction
    public const CATEGORIE_FORMATION = 'FORMATION';               // Attestation de formation
    public const CATEGORIE_AUTRE = 'AUTRE';

    // Niveaux de confidentialité
    public const CONFIDENTIALITE_PUBLIC_INTERNE = 'PUBLIC_INTERNE'; // Visible par tout le personnel RH
    public const CONFIDENTIALITE_CONFIDENTIEL = 'CONFIDENTIEL';     // Visible RH et l'agent concerné
    public const CONFIDENTIALITE_SECRET_RH = 'SECRET_RH';           // Restreint Direction RH / Administrateurs

    // Statuts de validation du document
    public const STATUT_VALIDE = 'VALIDE';
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_ARCHIVE = 'ARCHIVE';
    public const STATUT_REJETE = 'REJETE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 50)]
    private string $categorie = self::CATEGORIE_AUTRE;

    #[ORM\Column(length: 50)]
    private string $niveau_confidentialite = self::CONFIDENTIALITE_CONFIDENTIEL;

    #[ORM\Column(length: 50)]
    private string $statut = self::STATUT_VALIDE;

    #[ORM\Column(length: 255)]
    private ?string $fichier_nom_original = null;

    #[ORM\Column(length: 255)]
    private ?string $fichier_nom_stockage = null;

    #[ORM\Column(length: 100)]
    private ?string $type_mime = null;

    #[ORM\Column]
    private int $taille = 0; // Taille en octets

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $fichier_hash = null; // Checksum SHA-256

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_document = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(targetEntity: Affectation::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Affectation $affectation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $ajoute_par = null;

    /**
     * @var Collection<int, DocumentVersion>
     */
    #[ORM\OneToMany(targetEntity: DocumentVersion::class, mappedBy: 'document', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['numero_version' => 'DESC'])]
    private Collection $versions;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->categorie = self::CATEGORIE_AUTRE;
        $this->niveau_confidentialite = self::CONFIDENTIALITE_CONFIDENTIEL;
        $this->statut = self::STATUT_VALIDE;
        $this->versions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getCategorie(): string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getNiveauConfidentialite(): string
    {
        return $this->niveau_confidentialite;
    }

    public function setNiveauConfidentialite(string $niveau_confidentialite): static
    {
        $this->niveau_confidentialite = $niveau_confidentialite;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
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

    public function getTypeMime(): ?string
    {
        return $this->type_mime;
    }

    public function setTypeMime(string $type_mime): static
    {
        $this->type_mime = $type_mime;
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

    public function getFichierHash(): ?string
    {
        return $this->fichier_hash;
    }

    public function setFichierHash(?string $fichier_hash): static
    {
        $this->fichier_hash = $fichier_hash;
        return $this;
    }

    public function getDateDocument(): ?\DateTimeInterface
    {
        return $this->date_document;
    }

    public function setDateDocument(?\DateTimeInterface $date_document): static
    {
        $this->date_document = $date_document;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
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

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        $this->employe = $employe;
        if ($employe && !$employe->getDocuments()->contains($this)) {
            $employe->getDocuments()->add($this);
        }
        return $this;
    }

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }

    public function setAffectation(?Affectation $affectation): static
    {
        $this->affectation = $affectation;
        return $this;
    }

    public function getAjoutePar(): ?User
    {
        return $this->ajoute_par;
    }

    public function setAjoutePar(?User $ajoute_par): static
    {
        $this->ajoute_par = $ajoute_par;
        return $this;
    }

    /**
     * @return Collection<int, DocumentVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(DocumentVersion $version): static
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
            $version->setDocument($this);
        }
        return $this;
    }

    public function removeVersion(DocumentVersion $version): static
    {
        if ($this->versions->removeElement($version)) {
            if ($version->getDocument() === $this) {
                $version->setDocument(null);
            }
        }
        return $this;
    }

    public function getDerniereVersion(): ?DocumentVersion
    {
        return $this->versions->first() ?: null;
    }

    public function getNumeroDerniereVersion(): int
    {
        $latest = $this->getDerniereVersion();
        return $latest ? $latest->getNumeroVersion() : 1;
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

    public function isPdf(): bool
    {
        return $this->type_mime === 'application/pdf' || str_ends_with(strtolower($this->fichier_nom_original ?? ''), '.pdf');
    }

    public function isImage(): bool
    {
        return in_array($this->type_mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    }

    public function getExtension(): string
    {
        return strtolower(pathinfo($this->fichier_nom_original ?? '', PATHINFO_EXTENSION));
    }

    public function getIconClass(): string
    {
        if ($this->isPdf()) {
            return 'fa-file-pdf text-danger';
        }
        if ($this->isImage()) {
            return 'fa-file-image text-info';
        }
        $ext = $this->getExtension();
        return match ($ext) {
            'doc', 'docx' => 'fa-file-word text-primary',
            'xls', 'xlsx' => 'fa-file-excel text-success',
            'zip', 'rar', 'tar', 'gz' => 'fa-file-archive text-warning',
            default => 'fa-file-alt text-secondary',
        };
    }

    public function getCategorieLabel(): string
    {
        return match ($this->categorie) {
            self::CATEGORIE_IDENTITE => 'Pièce d\'identité & État civil',
            self::CATEGORIE_CONTRAT => 'Contrat & Engagement',
            self::CATEGORIE_DIPLOME_CV => 'Diplôme, Certification & CV',
            self::CATEGORIE_ACTE_ADMIN => 'Acte administratif (Arrêté, Décret)',
            self::CATEGORIE_AFFECTATION => 'Mouvement & Affectation (PV, Note)',
            self::CATEGORIE_EVALUATION => 'Évaluation & Notation',
            self::CATEGORIE_MEDICAL => 'Dossier Médical & Aptitude',
            self::CATEGORIE_DISCIPLINE => 'Dossier Disciplinaire',
            self::CATEGORIE_FORMATION => 'Formation continue',
            default => 'Autre pièce justificative',
        };
    }

    public function getNiveauConfidentialiteLabel(): string
    {
        return match ($this->niveau_confidentialite) {
            self::CONFIDENTIALITE_PUBLIC_INTERNE => 'Interne RH',
            self::CONFIDENTIALITE_CONFIDENTIEL => 'Confidentiel (Agent & RH)',
            self::CONFIDENTIALITE_SECRET_RH => 'Strictement Restreint (Direction RH)',
            default => $this->niveau_confidentialite,
        };
    }

    public function getStatutLabel(): string
    {
        return match ($this->statut) {
            self::STATUT_VALIDE => 'Validé / Authentifié',
            self::STATUT_EN_ATTENTE => 'En attente de vérification',
            self::STATUT_ARCHIVE => 'Archivé',
            self::STATUT_REJETE => 'Rejeté / Invalide',
            default => $this->statut,
        };
    }
}
