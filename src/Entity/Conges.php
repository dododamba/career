<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\CongesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CongesRepository::class)]
#[ORM\Table(name: 'conges')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_conges_institution')]
#[ORM\Index(columns: ['statut'], name: 'idx_conges_statut')]
#[ORM\Index(columns: ['date_debut', 'date_fin'], name: 'idx_conges_dates')]
class Conges implements TenantAwareInterface
{
    public const STATUT_EN_ATTENTE = 0;
    public const STATUT_APPROUVE = 1;
    public const STATUT_REFUSE = 2;
    public const STATUT_ANNULE = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'conges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $dateDiff = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $statut = self::STATUT_EN_ATTENTE;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?TypeConges $typeConge = null;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Document $document = null;

    #[ORM\ManyToOne(targetEntity: Acte::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Acte $acte = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireRefus = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $approuvePar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateApprobation = null;

    #[ORM\OneToMany(mappedBy: 'conges', targetEntity: CongesLog::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $congesLogs;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    public function __construct()
    {
        $this->congesLogs = new ArrayCollection();
        $this->statut = self::STATUT_EN_ATTENTE;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): self
    {
        $this->employe = $employe;
        if ($employe !== null && $this->institution === null && $employe->getInstitution() !== null) {
            $this->institution = $employe->getInstitution();
        }

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

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getDateDiff(): ?int
    {
        return $this->dateDiff;
    }

    public function setDateDiff(int $dateDiff): self
    {
        $this->dateDiff = $dateDiff;

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

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getTypeConge(): ?TypeConges
    {
        return $this->typeConge;
    }

    public function setTypeConge(?TypeConges $typeConge): self
    {
        $this->typeConge = $typeConge;

        return $this;
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

    public function getCommentaireRefus(): ?string
    {
        return $this->commentaireRefus;
    }

    public function setCommentaireRefus(?string $commentaireRefus): self
    {
        $this->commentaireRefus = $commentaireRefus;
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

    public function getDateApprobation(): ?\DateTimeInterface
    {
        return $this->dateApprobation;
    }

    public function setDateApprobation(?\DateTimeInterface $dateApprobation): self
    {
        $this->dateApprobation = $dateApprobation;
        return $this;
    }

    /**
     * @return Collection<int, CongesLog>
     */
    public function getCongesLogs(): Collection
    {
        return $this->congesLogs;
    }

    /**
     * Alias for getCongesLogs for Twig template compatibility.
     *
     * @return Collection<int, CongesLog>
     */
    public function getLogs(): Collection
    {
        return $this->congesLogs;
    }

    public function getCommentaireManager(): ?string
    {
        return $this->commentaireRefus;
    }

    public function addCongesLog(CongesLog $congesLog): self
    {
        if (!$this->congesLogs->contains($congesLog)) {
            $this->congesLogs->add($congesLog);
            $congesLog->setConges($this);
        }

        return $this;
    }

    public function removeCongesLog(CongesLog $congesLog): self
    {
        if ($this->congesLogs->removeElement($congesLog)) {
            if ($congesLog->getConges() === $this) {
                $congesLog->setConges(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatutLibelle(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_APPROUVE => 'Approuvé',
            self::STATUT_REFUSE => 'Refusé',
            self::STATUT_ANNULE => 'Annulé',
            default => 'Inconnu',
        };
    }

    public function getStatutBadgeClass(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'warning text-dark',
            self::STATUT_APPROUVE => 'success',
            self::STATUT_REFUSE => 'danger',
            self::STATUT_ANNULE => 'secondary',
            default => 'light text-dark',
        };
    }

    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function isApprouve(): bool
    {
        return $this->statut === self::STATUT_APPROUVE;
    }

    public function isRefuse(): bool
    {
        return $this->statut === self::STATUT_REFUSE;
    }

    public function isAnnule(): bool
    {
        return $this->statut === self::STATUT_ANNULE;
    }
}