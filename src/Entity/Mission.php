<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\MissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MissionRepository::class)]
#[ORM\Table(name: 'mission')]
#[ORM\Index(columns: ['statut'], name: 'idx_mission_statut')]
#[ORM\Index(columns: ['date_debut', 'date_fin'], name: 'idx_mission_dates')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_mission_institution')]
#[ORM\Index(columns: ['reference'], name: 'idx_mission_reference')]
class Mission implements TenantAwareInterface
{
    // Statuts du circuit de mission
    public const STATUT_BROUILLON = 0;
    public const STATUT_SOUMISE = 1;
    public const STATUT_VALIDEE = 2;
    public const STATUT_EN_COURS = 3;
    public const STATUT_TERMINEE = 4;
    public const STATUT_CLOTUREE = 5;
    public const STATUT_ANNULEE = 6;
    public const STATUT_REFUSEE = 7;

    public const STATUT_LABELS = [
        self::STATUT_BROUILLON => 'Brouillon',
        self::STATUT_SOUMISE => 'Soumise pour validation',
        self::STATUT_VALIDEE => 'Validée (Ordre émis)',
        self::STATUT_EN_COURS => 'En cours d\'exécution',
        self::STATUT_TERMINEE => 'Terminée (Rapport en attente)',
        self::STATUT_CLOTUREE => 'Clôturée',
        self::STATUT_ANNULEE => 'Annulée',
        self::STATUT_REFUSEE => 'Refusée',
    ];

    public const STATUT_BADGES = [
        self::STATUT_BROUILLON => 'badge-soft-secondary',
        self::STATUT_SOUMISE => 'badge-soft-warning',
        self::STATUT_VALIDEE => 'badge-soft-primary',
        self::STATUT_EN_COURS => 'badge-soft-info',
        self::STATUT_TERMINEE => 'badge-soft-warning',
        self::STATUT_CLOTUREE => 'badge-soft-success',
        self::STATUT_ANNULEE => 'badge-soft-dark',
        self::STATUT_REFUSEE => 'badge-soft-danger',
    ];

    // Modes de transport
    public const TRANSPORT_VEHICULE_SERVICE = 'VEHICULE_SERVICE';
    public const TRANSPORT_VEHICULE_PERSONNEL = 'VEHICULE_PERSONNEL';
    public const TRANSPORT_AVION = 'AVION';
    public const TRANSPORT_TRAIN = 'TRAIN';
    public const TRANSPORT_COMMUN = 'TRANSPORT_COMMUN';
    public const TRANSPORT_AUTRE = 'AUTRE';

    public const TRANSPORTS = [
        'Véhicule de service' => self::TRANSPORT_VEHICULE_SERVICE,
        'Véhicule personnel' => self::TRANSPORT_VEHICULE_PERSONNEL,
        'Avion' => self::TRANSPORT_AVION,
        'Train' => self::TRANSPORT_TRAIN,
        'Transport en commun / Car' => self::TRANSPORT_COMMUN,
        'Autre moyen' => self::TRANSPORT_AUTRE,
    ];

    // Prise en charge financière
    public const PRISE_EN_CHARGE_TOTAL_INSTITUTION = 'TOTALEMENT_INSTITUTION';
    public const PRISE_EN_CHARGE_PARTIEL_INSTITUTION = 'PARTIELLEMENT_INSTITUTION';
    public const PRISE_EN_CHARGE_ORGANISME_ACCUEIL = 'ORGANISME_ACCUEIL';
    public const PRISE_EN_CHARGE_SANS_FRAIS = 'SANS_FRAIS';

    public const PRISES_EN_CHARGE = [
        'Totalement par l\'institution' => self::PRISE_EN_CHARGE_TOTAL_INSTITUTION,
        'Partiellement par l\'institution' => self::PRISE_EN_CHARGE_PARTIEL_INSTITUTION,
        'Par l\'organisme d\'accueil / partenaire' => self::PRISE_EN_CHARGE_ORGANISME_ACCUEIL,
        'Sans frais pour l\'administration' => self::PRISE_EN_CHARGE_SANS_FRAIS,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\Column(length: 50)]
    private string $reference;

    #[ORM\Column(length: 255)]
    private string $objet;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private string $destination;

    #[ORM\Column(length: 100, options: ['default' => 'Côte d\'Ivoire'])]
    private string $pays = 'Côte d\'Ivoire';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieuPrecis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $itineraire = null;

    #[ORM\Column(length: 50, options: ['default' => self::TRANSPORT_VEHICULE_SERVICE])]
    private string $modeTransport = self::TRANSPORT_VEHICULE_SERVICE;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $dureeJours = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $budgetEstime = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $imputationBudgetaire = null;

    #[ORM\Column(length: 50, options: ['default' => self::PRISE_EN_CHARGE_TOTAL_INSTITUTION])]
    private string $priseEnCharge = self::PRISE_EN_CHARGE_TOTAL_INSTITUTION;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $statut = self::STATUT_BROUILLON;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Employe $chefMission = null;

    #[ORM\ManyToOne(targetEntity: Acte::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Acte $acte = null;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Document $documentRapport = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rapportTexte = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRapport = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $validePar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireValidation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $creePar = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, MissionParticipant>
     */
    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: MissionParticipant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participants;

    /**
     * @var Collection<int, MissionLog>
     */
    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: MissionLog::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $missionLogs;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->missionLogs = new ArrayCollection();
        $this->statut = self::STATUT_BROUILLON;
        $this->modeTransport = self::TRANSPORT_VEHICULE_SERVICE;
        $this->priseEnCharge = self::PRISE_EN_CHARGE_TOTAL_INSTITUTION;
        $this->pays = 'Côte d\'Ivoire';
        $this->dureeJours = 1;
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

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getObjet(): string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): self
    {
        $this->objet = $objet;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function getPays(): string
    {
        return $this->pays;
    }

    public function setPays(string $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getLieuPrecis(): ?string
    {
        return $this->lieuPrecis;
    }

    public function setLieuPrecis(?string $lieuPrecis): self
    {
        $this->lieuPrecis = $lieuPrecis;

        return $this;
    }

    public function getItineraire(): ?string
    {
        return $this->itineraire;
    }

    public function setItineraire(?string $itineraire): self
    {
        $this->itineraire = $itineraire;

        return $this;
    }

    public function getModeTransport(): string
    {
        return $this->modeTransport;
    }

    public function setModeTransport(string $modeTransport): self
    {
        $this->modeTransport = $modeTransport;

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

    public function getDureeJours(): int
    {
        return $this->dureeJours;
    }

    public function setDureeJours(int $dureeJours): self
    {
        $this->dureeJours = $dureeJours;

        return $this;
    }

    public function getBudgetEstime(): ?string
    {
        return $this->budgetEstime;
    }

    public function setBudgetEstime(?string $budgetEstime): self
    {
        $this->budgetEstime = $budgetEstime;

        return $this;
    }

    public function getImputationBudgetaire(): ?string
    {
        return $this->imputationBudgetaire;
    }

    public function setImputationBudgetaire(?string $imputationBudgetaire): self
    {
        $this->imputationBudgetaire = $imputationBudgetaire;

        return $this;
    }

    public function getPriseEnCharge(): string
    {
        return $this->priseEnCharge;
    }

    public function setPriseEnCharge(string $priseEnCharge): self
    {
        $this->priseEnCharge = $priseEnCharge;

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

    public function getChefMission(): ?Employe
    {
        return $this->chefMission;
    }

    public function setChefMission(?Employe $chefMission): self
    {
        $this->chefMission = $chefMission;

        return $this;
    }

    public function getActe(): ?Acte
    {
        return $this->acte;
    }

    public function getOrdreMission(): ?Acte
    {
        return $this->acte;
    }

    public function setActe(?Acte $acte): self
    {
        $this->acte = $acte;

        return $this;
    }

    public function getDocumentRapport(): ?Document
    {
        return $this->documentRapport;
    }

    public function setDocumentRapport(?Document $documentRapport): self
    {
        $this->documentRapport = $documentRapport;

        return $this;
    }

    public function getRapportTexte(): ?string
    {
        return $this->rapportTexte;
    }

    public function setRapportTexte(?string $rapportTexte): self
    {
        $this->rapportTexte = $rapportTexte;

        return $this;
    }

    public function getDateRapport(): ?\DateTimeInterface
    {
        return $this->dateRapport;
    }

    public function setDateRapport(?\DateTimeInterface $dateRapport): self
    {
        $this->dateRapport = $dateRapport;

        return $this;
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
     * @return Collection<int, MissionParticipant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(MissionParticipant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setMission($this);
        }

        return $this;
    }

    public function removeParticipant(MissionParticipant $participant): self
    {
        if ($this->participants->removeElement($participant)) {
            if ($participant->getMission() === $this) {
                $participant->setMission(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MissionLog>
     */
    public function getMissionLogs(): Collection
    {
        return $this->missionLogs;
    }

    public function addMissionLog(MissionLog $log): self
    {
        if (!$this->missionLogs->contains($log)) {
            $this->missionLogs->add($log);
            $log->setMission($this);
        }

        return $this;
    }

    public function removeMissionLog(MissionLog $log): self
    {
        if ($this->missionLogs->removeElement($log)) {
            if ($log->getMission() === $this) {
                $log->setMission(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si un employé fait partie de la mission.
     */
    public function hasParticipant(Employe $employe): bool
    {
        if ($this->chefMission && $this->chefMission->getId() === $employe->getId()) {
            return true;
        }

        foreach ($this->participants as $participant) {
            if ($participant->getEmploye() && $participant->getEmploye()->getId() === $employe->getId()) {
                return true;
            }
        }

        return false;
    }
}
