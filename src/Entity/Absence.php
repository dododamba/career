<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\AbsenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
#[ORM\Table(name: 'absence')]
#[ORM\Index(columns: ['statut'], name: 'idx_absence_statut')]
#[ORM\Index(columns: ['date_debut', 'date_fin'], name: 'idx_absence_dates')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_absence_institution')]
#[ORM\Index(columns: ['employe_id'], name: 'idx_absence_employe')]
class Absence implements TenantAwareInterface
{
    public const STATUT_EN_ATTENTE = 0;
    public const STATUT_APPROUVE = 1;
    public const STATUT_REFUSE = 2;
    public const STATUT_ANNULE = 3;

    public const STATUT_LABELS = [
        self::STATUT_EN_ATTENTE => 'En attente',
        self::STATUT_APPROUVE => 'Accordée',
        self::STATUT_REFUSE => 'Refusée',
        self::STATUT_ANNULE => 'Annulée',
    ];

    public const STATUT_BADGES = [
        self::STATUT_EN_ATTENTE => 'badge-soft-warning',
        self::STATUT_APPROUVE => 'badge-soft-success',
        self::STATUT_REFUSE => 'badge-soft-danger',
        self::STATUT_ANNULE => 'badge-soft-secondary',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'absences')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(targetEntity: TypeAbsence::class, inversedBy: 'absences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeAbsence $typeAbsence = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $duree = '1.00';

    #[ORM\Column(length: 20, options: ['default' => 'JOURS'])]
    private string $unite = 'JOURS';

    #[ORM\Column(type: Types::TEXT)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $statut = self::STATUT_EN_ATTENTE;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Document $document = null;

    #[ORM\ManyToOne(targetEntity: Acte::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Acte $acte = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $approuvePar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDecision = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireDecision = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $creePar = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, AbsenceLog>
     */
    #[ORM\OneToMany(mappedBy: 'absence', targetEntity: AbsenceLog::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $absenceLogs;

    public function __construct()
    {
        $this->absenceLogs = new ArrayCollection();
        $this->statut = self::STATUT_EN_ATTENTE;
        $this->unite = 'JOURS';
        $this->duree = '1.00';
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): self
    {
        $this->employe = $employe;

        return $this;
    }

    public function getTypeAbsence(): ?TypeAbsence
    {
        return $this->typeAbsence;
    }

    public function setTypeAbsence(?TypeAbsence $typeAbsence): self
    {
        $this->typeAbsence = $typeAbsence;

        return $this;
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

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTimeInterface $heureDebut): self
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeInterface $heureFin): self
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function getDuree(): ?string
    {
        return $this->duree;
    }

    public function setDuree(?string $duree): self
    {
        $this->duree = $duree;

        return $this;
    }

    public function getUnite(): string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): self
    {
        $this->unite = $unite;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
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

    public function getStatutBadge(): string
    {
        return self::STATUT_BADGES[$this->statut] ?? 'badge-soft-secondary';
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): self
    {
        $this->document = $document;

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

    public function getApprouvePar(): ?User
    {
        return $this->approuvePar;
    }

    public function setApprouvePar(?User $approuvePar): self
    {
        $this->approuvePar = $approuvePar;

        return $this;
    }

    public function getDateDecision(): ?\DateTimeInterface
    {
        return $this->dateDecision;
    }

    public function setDateDecision(?\DateTimeInterface $dateDecision): self
    {
        $this->dateDecision = $dateDecision;

        return $this;
    }

    public function getCommentaireDecision(): ?string
    {
        return $this->commentaireDecision;
    }

    public function setCommentaireDecision(?string $commentaireDecision): self
    {
        $this->commentaireDecision = $commentaireDecision;

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
     * @return Collection<int, AbsenceLog>
     */
    public function getAbsenceLogs(): Collection
    {
        return $this->absenceLogs;
    }

    public function addAbsenceLog(AbsenceLog $absenceLog): self
    {
        if (!$this->absenceLogs->contains($absenceLog)) {
            $this->absenceLogs->add($absenceLog);
            $absenceLog->setAbsence($this);
        }

        return $this;
    }

    public function removeAbsenceLog(AbsenceLog $absenceLog): self
    {
        if ($this->absenceLogs->removeElement($absenceLog)) {
            if ($absenceLog->getAbsence() === $this) {
                $absenceLog->setAbsence(null);
            }
        }

        return $this;
    }
}
