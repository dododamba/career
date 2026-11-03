<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\AffectationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
#[ORM\Table(name: 'affectation')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_affectation_institution')]
#[ORM\Index(columns: ['employe_id'], name: 'idx_affectation_employe')]
#[ORM\Index(columns: ['poste_id'], name: 'idx_affectation_poste')]
#[ORM\Index(columns: ['structure_id'], name: 'idx_affectation_structure')]
#[ORM\Index(columns: ['statut'], name: 'idx_affectation_statut')]
#[ORM\Index(columns: ['type_mouvement'], name: 'idx_affectation_type')]
class Affectation implements TenantAwareInterface
{
    public const TYPE_AFFECTATION_INITIALE = 'AFFECTATION_INITIALE';
    public const TYPE_MUTATION = 'MUTATION';
    public const TYPE_PROMOTION = 'PROMOTION';
    public const TYPE_CHANGEMENT_POSTE = 'CHANGEMENT_POSTE';
    public const TYPE_DETACHEMENT = 'DETACHEMENT';
    public const TYPE_REINTEGRATION = 'REINTEGRATION';
    public const TYPE_CESSATION = 'CESSATION';

    public const STATUT_PROPOSEE = 'PROPOSEE';
    public const STATUT_VALIDEE = 'VALIDEE';
    public const STATUT_ACTIVE = 'ACTIVE';
    public const STATUT_CLOTUREE = 'CLOTUREE';
    public const STATUT_ANNULEE = 'ANNULEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $reference = null;

    #[ORM\Column(length: 50)]
    private string $type_mouvement = self::TYPE_AFFECTATION_INITIALE;

    #[ORM\Column(length: 50)]
    private string $statut = self::STATUT_ACTIVE;

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    // Origine avant le mouvement
    #[ORM\ManyToOne(targetEntity: Structure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $ancienne_structure = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Service $ancien_service = null;

    #[ORM\ManyToOne(targetEntity: Poste::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Poste $ancien_poste = null;

    #[ORM\ManyToOne(targetEntity: Fonction::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Fonction $ancienne_fonction = null;

    // Destination du mouvement
    #[ORM\ManyToOne(targetEntity: Structure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $structure = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Poste::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Poste $poste = null;

    #[ORM\ManyToOne(targetEntity: Fonction::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Fonction $fonction = null;

    // Dates clés
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_decision = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_effet = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_prise_service = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_fin = null;

    // Références administratives
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference_acte = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(mappedBy: 'affectation', targetEntity: Document::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $documents;

    public function __construct()
    {
        $this->date_decision = new \DateTime();
        $this->date_effet = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->statut = self::STATUT_ACTIVE;
        $this->type_mouvement = self::TYPE_AFFECTATION_INITIALE;
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getTypeMouvement(): string
    {
        return $this->type_mouvement;
    }

    public function setTypeMouvement(string $type_mouvement): static
    {
        $this->type_mouvement = $type_mouvement;
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

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        $this->employe = $employe;
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

    public function getAncienneStructure(): ?Structure
    {
        return $this->ancienne_structure;
    }

    public function setAncienneStructure(?Structure $ancienne_structure): static
    {
        $this->ancienne_structure = $ancienne_structure;
        return $this;
    }

    public function getAncienService(): ?Service
    {
        return $this->ancien_service;
    }

    public function setAncienService(?Service $ancien_service): static
    {
        $this->ancien_service = $ancien_service;
        return $this;
    }

    public function getAncienPoste(): ?Poste
    {
        return $this->ancien_poste;
    }

    public function setAncienPoste(?Poste $ancien_poste): static
    {
        $this->ancien_poste = $ancien_poste;
        return $this;
    }

    public function getAncienneFonction(): ?Fonction
    {
        return $this->ancienne_fonction;
    }

    public function setAncienneFonction(?Fonction $ancienne_fonction): static
    {
        $this->ancienne_fonction = $ancienne_fonction;
        return $this;
    }

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure): static
    {
        $this->structure = $structure;
        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getPoste(): ?Poste
    {
        return $this->poste;
    }

    public function setPoste(?Poste $poste): static
    {
        $this->poste = $poste;
        return $this;
    }

    public function getFonction(): ?Fonction
    {
        return $this->fonction;
    }

    public function setFonction(?Fonction $fonction): static
    {
        $this->fonction = $fonction;
        return $this;
    }

    public function getDateDecision(): ?\DateTimeInterface
    {
        return $this->date_decision;
    }

    public function setDateDecision(\DateTimeInterface $date_decision): static
    {
        $this->date_decision = $date_decision;
        return $this;
    }

    public function getDateEffet(): ?\DateTimeInterface
    {
        return $this->date_effet;
    }

    public function setDateEffet(\DateTimeInterface $date_effet): static
    {
        $this->date_effet = $date_effet;
        return $this;
    }

    public function getDatePriseService(): ?\DateTimeInterface
    {
        return $this->date_prise_service;
    }

    public function setDatePriseService(?\DateTimeInterface $date_prise_service): static
    {
        $this->date_prise_service = $date_prise_service;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTimeInterface $date_fin): static
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    public function getReferenceActe(): ?string
    {
        return $this->reference_acte;
    }

    public function setReferenceActe(?string $reference_acte): static
    {
        $this->reference_acte = $reference_acte;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
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

    public function isActive(): bool
    {
        return $this->statut === self::STATUT_ACTIVE;
    }

    public function getTypeMouvementLabel(): string
    {
        return match ($this->type_mouvement) {
            self::TYPE_AFFECTATION_INITIALE => 'Affectation initiale / Recrutement',
            self::TYPE_MUTATION => 'Mutation inter-services / structures',
            self::TYPE_PROMOTION => 'Promotion de grade / fonction',
            self::TYPE_CHANGEMENT_POSTE => 'Changement de poste interne',
            self::TYPE_DETACHEMENT => 'Détachement',
            self::TYPE_REINTEGRATION => 'Réintégration',
            self::TYPE_CESSATION => 'Cessation définitive de fonction',
            default => $this->type_mouvement,
        };
    }

    public function getStatutLabel(): string
    {
        return match ($this->statut) {
            self::STATUT_ACTIVE => 'En cours / Active',
            self::STATUT_VALIDEE => 'Validée (Prise de service en attente)',
            self::STATUT_PROPOSEE => 'En projet / Proposition',
            self::STATUT_CLOTUREE => 'Clôturée (Historique)',
            self::STATUT_ANNULEE => 'Annulée',
            default => $this->statut,
        };
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setAffectation($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getAffectation() === $this) {
                $document->setAffectation(null);
            }
        }

        return $this;
    }
}
