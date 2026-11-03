<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\TypeActeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeActeRepository::class)]
#[ORM\Table(name: 'type_acte')]
#[ORM\Index(columns: ['code'], name: 'idx_type_acte_code')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_type_acte_institution')]
class TypeActe implements TenantAwareInterface
{
    public const CODE_DECRET = 'DECRET';
    public const CODE_ARRETE = 'ARRETE';
    public const CODE_DECISION = 'DECISION';
    public const CODE_NOTE_SERVICE = 'NOTE_SERVICE';
    public const CODE_ORDRE_MISSION = 'ORDRE_MISSION';
    public const CODE_TITRE_CONGE = 'TITRE_CONGE';
    public const CODE_AUTORISATION_ABSENCE = 'AUTORISATION_ABSENCE';
    public const CODE_PV_PRISE_SERVICE = 'PV_PRISE_SERVICE';
    public const CODE_PV_CESSATION_SERVICE = 'PV_CESSATION_SERVICE';
    public const CODE_DECISION_INTERIM = 'DECISION_INTERIM';
    public const CODE_AUTRE = 'AUTRE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $code = self::CODE_ARRETE;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $actif = true;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    /**
     * @var Collection<int, Acte>
     */
    #[ORM\OneToMany(mappedBy: 'type_acte', targetEntity: Acte::class)]
    private Collection $actes;

    public function __construct()
    {
        $this->actes = new ArrayCollection();
        $this->actif = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper($code);
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
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

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
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

    /**
     * @return Collection<int, Acte>
     */
    public function getActes(): Collection
    {
        return $this->actes;
    }

    public function addActe(Acte $acte): static
    {
        if (!$this->actes->contains($acte)) {
            $this->actes->add($acte);
            $acte->setTypeActe($this);
        }
        return $this;
    }

    public function removeActe(Acte $acte): static
    {
        if ($this->actes->removeElement($acte)) {
            if ($acte->getTypeActe() === $this) {
                $acte->setTypeActe(null);
            }
        }
        return $this;
    }

    public function getBadgeClass(): string
    {
        return match ($this->code) {
            self::CODE_DECRET => 'bg-danger',
            self::CODE_ARRETE => 'bg-primary',
            self::CODE_DECISION => 'bg-success',
            self::CODE_NOTE_SERVICE => 'bg-info',
            self::CODE_ORDRE_MISSION => 'bg-warning text-dark',
            self::CODE_TITRE_CONGE => 'bg-secondary',
            self::CODE_PV_PRISE_SERVICE, self::CODE_PV_CESSATION_SERVICE => 'bg-dark',
            default => 'bg-light text-dark border',
        };
    }
}
