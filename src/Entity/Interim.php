<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\InterimRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterimRepository::class)]
#[ORM\Table(name: 'interims')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_interim_institution')]
#[ORM\Index(columns: ['titulaire_id'], name: 'idx_interim_titulaire')]
#[ORM\Index(columns: ['interimaire_id'], name: 'idx_interim_interimaire')]
#[ORM\Index(columns: ['statut'], name: 'idx_interim_statut')]
#[ORM\Index(columns: ['date_debut'], name: 'idx_interim_date_debut')]
#[ORM\Index(columns: ['date_fin'], name: 'idx_interim_date_fin')]
class Interim implements TenantAwareInterface
{
    public const STATUT_BROUILLON = 0;
    public const STATUT_SOUMIS = 1;
    public const STATUT_ACTIF = 2;
    public const STATUT_TERMINE = 3;
    public const STATUT_REVOQUE = 4;
    public const STATUT_REFUSE = 5;

    public const STATUT_LABELS = [
        self::STATUT_BROUILLON => 'Brouillon',
        self::STATUT_SOUMIS => 'En attente de validation',
        self::STATUT_ACTIF => 'Actif (En cours)',
        self::STATUT_TERMINE => 'Terminé / Échu',
        self::STATUT_REVOQUE => 'Révoqué',
        self::STATUT_REFUSE => 'Refusé',
    ];

    public const STATUT_BADGES = [
        self::STATUT_BROUILLON => ['class' => 'secondary', 'label' => 'Brouillon'],
        self::STATUT_SOUMIS => ['class' => 'warning text-dark', 'label' => 'En attente'],
        self::STATUT_ACTIF => ['class' => 'success', 'label' => 'Actif'],
        self::STATUT_TERMINE => ['class' => 'dark', 'label' => 'Terminé'],
        self::STATUT_REVOQUE => ['class' => 'danger', 'label' => 'Révoqué'],
        self::STATUT_REFUSE => ['class' => 'danger', 'label' => 'Refusé'],
    ];

    public const TYPE_INTERIM_TOTAL = 'INTERIM_TOTAL';
    public const TYPE_DELEGATION_SIGNATURE = 'DELEGATION_SIGNATURE';
    public const TYPE_DELEGATION_POUVOIR = 'DELEGATION_POUVOIR';
    public const TYPE_GESTION_COURANTE = 'GESTION_COURANTE';

    public const TYPES_DELEGATION = [
        self::TYPE_INTERIM_TOTAL => 'Intérim total (Remplacement intégral du titulaire)',
        self::TYPE_DELEGATION_SIGNATURE => 'Délégation de signature administrative',
        self::TYPE_DELEGATION_POUVOIR => 'Délégation de pouvoirs spécifiques',
        self::TYPE_GESTION_COURANTE => 'Expédition des affaires courantes',
    ];

    public const POUVOIR_TOUS = 'TOUS_POUVOIRS';
    public const POUVOIR_VALIDATION_CONGES = 'VALIDATION_CONGES';
    public const POUVOIR_SIGNATURE_COURRIER = 'SIGNATURE_COURRIER';
    public const POUVOIR_ORDONNANCEMENT_DEPENSES = 'ORDONNANCEMENT_DEPENSES';
    public const POUVOIR_EVALUATION_AGENTS = 'EVALUATION_AGENTS';
    public const POUVOIR_REPRESENTATION = 'REPRESENTATION';
    public const POUVOIR_GESTION_EQUIPE = 'GESTION_EQUIPE';

    public const POUVOIRS_LABELS = [
        self::POUVOIR_TOUS => 'Plein pouvoir (toutes les prérogatives du poste)',
        self::POUVOIR_VALIDATION_CONGES => 'Validation des congés et autorisations d\'absence',
        self::POUVOIR_SIGNATURE_COURRIER => 'Signature des courriers et notes administratives',
        self::POUVOIR_ORDONNANCEMENT_DEPENSES => 'Ordonnancement des dépenses et engagements budgétaires',
        self::POUVOIR_EVALUATION_AGENTS => 'Évaluation et notation des collaborateurs',
        self::POUVOIR_REPRESENTATION => 'Représentation officielle aux réunions et comités',
        self::POUVOIR_GESTION_EQUIPE => 'Gestion opérationnelle et plannings des équipes',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\Column(length: 50)]
    private ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Employe $titulaire = null;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Employe $interimaire = null;

    #[ORM\ManyToOne(targetEntity: Poste::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Poste $poste = null;

    #[ORM\ManyToOne(targetEntity: Structure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $structure = null;

    #[ORM\Column(length: 50)]
    private string $typeDelegation = self::TYPE_INTERIM_TOTAL;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $perimetre = [];

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $motif = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $statut = self::STATUT_BROUILLON;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $validePar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireValidation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinEffective = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifFin = null;

    #[ORM\ManyToOne(targetEntity: Acte::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Acte $acte = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $creePar = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, InterimLog>
     */
    #[ORM\OneToMany(mappedBy: 'interim', targetEntity: InterimLog::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $interimLogs;

    public function __construct()
    {
        $this->interimLogs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->statut = self::STATUT_BROUILLON;
        $this->perimetre = [self::POUVOIR_TOUS];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTitulaire(): ?Employe
    {
        return $this->titulaire;
    }

    public function setTitulaire(?Employe $titulaire): self
    {
        $this->titulaire = $titulaire;

        return $this;
    }

    public function getInterimaire(): ?Employe
    {
        return $this->interimaire;
    }

    public function setInterimaire(?Employe $interimaire): self
    {
        $this->interimaire = $interimaire;

        return $this;
    }

    public function getPoste(): ?Poste
    {
        return $this->poste;
    }

    public function setPoste(?Poste $poste): self
    {
        $this->poste = $poste;

        return $this;
    }

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure): self
    {
        $this->structure = $structure;

        return $this;
    }

    public function getTypeDelegation(): string
    {
        return $this->typeDelegation;
    }

    public function setTypeDelegation(string $typeDelegation): self
    {
        $this->typeDelegation = $typeDelegation;

        return $this;
    }

    public function getTypeDelegationLabel(): string
    {
        return self::TYPES_DELEGATION[$this->typeDelegation] ?? $this->typeDelegation;
    }

    public function getPerimetre(): ?array
    {
        return $this->perimetre ?? [];
    }

    public function setPerimetre(?array $perimetre): self
    {
        $this->perimetre = $perimetre;

        return $this;
    }

    public function hasPouvoir(string $pouvoir): bool
    {
        if (empty($this->perimetre)) {
            return false;
        }

        if (in_array(self::POUVOIR_TOUS, $this->perimetre, true)) {
            return true;
        }

        return in_array($pouvoir, $this->perimetre, true);
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getDureeEnJours(): int
    {
        if (!$this->dateDebut || !$this->dateFin) {
            return 0;
        }

        $diff = $this->dateDebut->diff($this->dateFin);

        return (int) $diff->days + 1;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): self
    {
        $this->motif = $motif;

        return $this;
    }

    public function getStatut(): int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getStatutLabel(): string
    {
        return self::STATUT_LABELS[$this->statut] ?? 'Inconnu';
    }

    public function getStatutBadge(): array
    {
        return self::STATUT_BADGES[$this->statut] ?? ['class' => 'secondary', 'label' => 'Inconnu'];
    }

    public function getValidePar(): ?User
    {
        return $this->validePar;
    }

    public function setValidePar(?User $validePar): self
    {
        $this->validePar = $validePar;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getCommentaireValidation(): ?string
    {
        return $this->commentaireValidation;
    }

    public function setCommentaireValidation(?string $commentaireValidation): self
    {
        $this->commentaireValidation = $commentaireValidation;

        return $this;
    }

    public function getDateFinEffective(): ?\DateTimeInterface
    {
        return $this->dateFinEffective;
    }

    public function setDateFinEffective(?\DateTimeInterface $dateFinEffective): self
    {
        $this->dateFinEffective = $dateFinEffective;

        return $this;
    }

    public function getMotifFin(): ?string
    {
        return $this->motifFin;
    }

    public function setMotifFin(?string $motifFin): self
    {
        $this->motifFin = $motifFin;

        return $this;
    }

    public function getActe(): ?Acte
    {
        return $this->acte;
    }

    public function setActe(?Acte $acte): self
    {
        $this->acte = $acte;

        return $this;
    }

    public function getCreePar(): ?User
    {
        return $this->creePar;
    }

    public function setCreePar(?User $creePar): self
    {
        $this->creePar = $creePar;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, InterimLog>
     */
    public function getInterimLogs(): Collection
    {
        return $this->interimLogs;
    }

    public function addInterimLog(InterimLog $interimLog): self
    {
        if (!$this->interimLogs->contains($interimLog)) {
            $this->interimLogs->add($interimLog);
            $interimLog->setInterim($this);
        }

        return $this;
    }

    public function removeInterimLog(InterimLog $interimLog): self
    {
        if ($this->interimLogs->removeElement($interimLog)) {
            if ($interimLog->getInterim() === $this) {
                $interimLog->setInterim(null);
            }
        }

        return $this;
    }

    public function isActifALaDate(?\DateTimeInterface $date = null): bool
    {
        if ($this->statut !== self::STATUT_ACTIF) {
            return false;
        }

        $dateComp = $date ?? new \DateTime();
        $dateStr = $dateComp->format('Y-m-d');
        $debStr = $this->dateDebut ? $this->dateDebut->format('Y-m-d') : null;
        $finStr = $this->dateFinEffective ? $this->dateFinEffective->format('Y-m-d') : ($this->dateFin ? $this->dateFin->format('Y-m-d') : null);

        if ($debStr && $dateStr < $debStr) {
            return false;
        }

        if ($finStr && $dateStr > $finStr) {
            return false;
        }

        return true;
    }
}
