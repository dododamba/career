<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\TypeCongesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeCongesRepository::class)]
#[ORM\Table(name: 'type_conges')]
#[ORM\Index(columns: ['code'], name: 'idx_type_conges_code')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_type_conges_institution')]
class TypeConges implements TenantAwareInterface
{
    public const CODE_ANNUEL = 'ANNUEL';
    public const CODE_MALADIE = 'MALADIE';
    public const CODE_MATERNITE = 'MATERNITE';
    public const CODE_PATERNITE = 'PATERNITE';
    public const CODE_EVENEMENT_FAMILIAL = 'EVENEMENT_FAMILIAL';
    public const CODE_SANS_SOLDE = 'SANS_SOLDE';
    public const CODE_RECUPERATION = 'RECUPERATION';
    public const CODE_AUTRE = 'AUTRE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = self::CODE_ANNUEL;

    #[ORM\Column(length: 100)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isDeductible = true;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $couleur = '#0d6efd';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 30])]
    private int $joursParAn = 30;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $justificatifRequis = false;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\OneToMany(mappedBy: 'typeConge', targetEntity: SoldeConge::class, orphanRemoval: true)]
    private Collection $soldesConges;

    #[ORM\Column]
    private ?bool $isActive = true;

    public function __construct()
    {
        $this->soldesConges = new ArrayCollection();
        $this->isActive = true;
        $this->isDeductible = true;
        $this->joursParAn = 30;
        $this->justificatifRequis = false;
        $this->couleur = '#0d6efd';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code ? strtoupper($code) : null;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getNom(): string
    {
        return $this->libelle ?? '';
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

    public function isIsDeductible(): ?bool
    {
        return $this->isDeductible;
    }

    public function isDeductible(): bool
    {
        return $this->isDeductible ?? false;
    }

    public function setIsDeductible(bool $isDeductible): self
    {
        $this->isDeductible = $isDeductible;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getJoursParAn(): int
    {
        return $this->joursParAn;
    }

    public function setJoursParAn(int $joursParAn): self
    {
        $this->joursParAn = $joursParAn;
        return $this;
    }

    public function isJustificatifRequis(): bool
    {
        return $this->justificatifRequis;
    }

    public function setJustificatifRequis(bool $justificatifRequis): self
    {
        $this->justificatifRequis = $justificatifRequis;
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
     * @return Collection<int, SoldeConge>
     */
    public function getSoldesConges(): Collection
    {
        return $this->soldesConges;
    }

    public function addSoldesConge(SoldeConge $soldesConge): self
    {
        if (!$this->soldesConges->contains($soldesConge)) {
            $this->soldesConges->add($soldesConge);
            $soldesConge->setTypeConge($this);
        }

        return $this;
    }

    public function removeSoldesConge(SoldeConge $soldesConge): self
    {
        if ($this->soldesConges->removeElement($soldesConge)) {
            if ($soldesConge->getTypeConge() === $this) {
                $soldesConge->setTypeConge(null);
            }
        }

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function isActive(): bool
    {
        return $this->isActive ?? true;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}
