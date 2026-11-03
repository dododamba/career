<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\PosteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PosteRepository::class)]
#[ORM\Table(name: 'poste')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_poste_institution')]
#[ORM\Index(columns: ['structure_id'], name: 'idx_poste_structure')]
#[ORM\Index(columns: ['service_id'], name: 'idx_poste_service')]
#[ORM\Index(columns: ['statut'], name: 'idx_poste_statut')]
#[ORM\Index(columns: ['reference'], name: 'idx_poste_reference')]
class Poste implements TenantAwareInterface
{
    public const STATUT_VACANT = 'VACANT';
    public const STATUT_OCCUPE = 'OCCUPE';
    public const STATUT_SUSPENDU = 'SUSPENDU';
    public const STATUT_FERME = 'FERME';

    public const NIVEAU_DIRECTION = 'DIRECTION';
    public const NIVEAU_CADRE_SUPERIEUR = 'CADRE_SUPERIEUR';
    public const NIVEAU_CADRE = 'CADRE';
    public const NIVEAU_MAITRISE = 'MAITRISE';
    public const NIVEAU_EXECUTION = 'EXECUTION';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    private ?string $intitule = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $competences_requises = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $niveau = self::NIVEAU_CADRE;

    #[ORM\Column(length: 50)]
    private string $statut = self::STATUT_VACANT;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_fermeture = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\ManyToOne(targetEntity: Structure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $structure = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Fonction::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Fonction $fonction = null;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Employe $occupant_actuel = null;

    /**
     * @var Collection<int, OccupationPoste>
     */
    #[ORM\OneToMany(mappedBy: 'poste', targetEntity: OccupationPoste::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['date_debut' => 'DESC'])]
    private Collection $occupations;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->occupations = new ArrayCollection();
        $this->statut = self::STATUT_VACANT;
        $this->date_creation = new \DateTime();
        $this->createdAt = new \DateTime();
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

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): static
    {
        $this->intitule = $intitule;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->intitule;
    }

    public function setTitre(string $titre): static
    {
        $this->intitule = $titre;
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

    public function getCompetencesRequises(): ?string
    {
        return $this->competences_requises;
    }

    public function setCompetencesRequises(?string $competences_requises): static
    {
        $this->competences_requises = $competences_requises;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(?string $niveau): static
    {
        $this->niveau = $niveau;
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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getDateFermeture(): ?\DateTimeInterface
    {
        return $this->date_fermeture;
    }

    public function setDateFermeture(?\DateTimeInterface $date_fermeture): static
    {
        $this->date_fermeture = $date_fermeture;
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

    public function getFonction(): ?Fonction
    {
        return $this->fonction;
    }

    public function setFonction(?Fonction $fonction): static
    {
        $this->fonction = $fonction;
        return $this;
    }

    public function getOccupantActuel(): ?Employe
    {
        return $this->occupant_actuel;
    }

    public function setOccupantActuel(?Employe $occupant_actuel): static
    {
        $this->occupant_actuel = $occupant_actuel;
        return $this;
    }

    /**
     * @return Collection<int, OccupationPoste>
     */
    public function getOccupations(): Collection
    {
        return $this->occupations;
    }

    public function addOccupation(OccupationPoste $occupation): static
    {
        if (!$this->occupations->contains($occupation)) {
            $this->occupations->add($occupation);
            $occupation->setPoste($this);
        }
        return $this;
    }

    public function removeOccupation(OccupationPoste $occupation): static
    {
        if ($this->occupations->removeElement($occupation)) {
            if ($occupation->getPoste() === $this) {
                $occupation->setPoste(null);
            }
        }
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

    public function isVacant(): bool
    {
        return $this->statut === self::STATUT_VACANT || $this->occupant_actuel === null;
    }

    public function isOccupe(): bool
    {
        return $this->statut === self::STATUT_OCCUPE && $this->occupant_actuel !== null;
    }

    public function isActif(): bool
    {
        return !in_array($this->statut, [self::STATUT_SUSPENDU, self::STATUT_FERME], true);
    }
}
