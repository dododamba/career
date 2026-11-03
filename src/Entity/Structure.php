<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\StructureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureRepository::class)]
#[ORM\Table(name: 'structure')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_structure_institution')]
#[ORM\Index(columns: ['parent_id'], name: 'idx_structure_parent')]
#[ORM\Index(columns: ['code'], name: 'idx_structure_code')]
#[ORM\Index(columns: ['statut'], name: 'idx_structure_statut')]
class Structure implements TenantAwareInterface
{
    public const TYPE_DIRECTION_GENERALE = 'DIRECTION_GENERALE';
    public const TYPE_DIRECTION = 'DIRECTION';
    public const TYPE_DEPARTEMENT = 'DEPARTEMENT';
    public const TYPE_DIVISION = 'DIVISION';
    public const TYPE_SERVICE = 'SERVICE';
    public const TYPE_AUTRE = 'AUTRE';

    public const STATUT_ACTIVE = 'ACTIVE';
    public const STATUT_INACTIVE = 'INACTIVE';
    public const STATUT_ARCHIVED = 'ARCHIVED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 50, options: ['default' => self::TYPE_DIRECTION])]
    private string $type = self::TYPE_DIRECTION;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUT_ACTIVE])]
    private string $statut = self::STATUT_ACTIVE;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'structures')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $parent = null;

    /**
     * @var Collection<int, Structure>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /**
     * @var Collection<int, Service>
     */
    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'structure')]
    private Collection $services;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Employe $responsable = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->statut = self::STATUT_ACTIVE;
        $this->type = self::TYPE_DIRECTION;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isActive(): bool
    {
        return $this->statut === self::STATUT_ACTIVE;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        $this->updatedAt = new \DateTimeImmutable();

        if ($parent !== null && !$parent->getChildren()->contains($this)) {
            $parent->getChildren()->add($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Structure>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Structure $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Structure $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setStructure($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        if ($this->services->removeElement($service)) {
            if ($service->getStructure() === $this) {
                $service->setStructure(null);
            }
        }

        return $this;
    }

    public function getResponsable(): ?Employe
    {
        return $this->responsable;
    }

    public function setResponsable(?Employe $responsable): static
    {
        $this->responsable = $responsable;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Structure #' . $this->id;
    }
}
