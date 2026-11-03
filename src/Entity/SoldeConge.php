<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\SoldeCongeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoldeCongeRepository::class)]
#[ORM\Table(name: 'solde_conge')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_solde_conge_institution')]
#[ORM\Index(columns: ['annee'], name: 'idx_solde_conge_annee')]
class SoldeConge implements TenantAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'soldesConges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(inversedBy: 'soldesConges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TypeConges $typeConge = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $annee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '30.00'])]
    private string $soldeAcquis = '30.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $soldePris = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $solde = '30.00';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->annee = (int) date('Y');
        $this->soldeAcquis = '30.00';
        $this->soldePris = '0.00';
        $this->solde = '30.00';
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
        if ($employe && $this->institution === null) {
            $this->institution = $employe->getInstitution();
        }

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

    public function getInstitution(): ?Institution
    {
        return $this->institution;
    }

    public function setInstitution(?Institution $institution): static
    {
        $this->institution = $institution;
        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(?int $annee): self
    {
        $this->annee = $annee;
        return $this;
    }

    public function getSoldeAcquis(): string
    {
        return $this->soldeAcquis;
    }

    public function setSoldeAcquis(string $soldeAcquis): self
    {
        $this->soldeAcquis = $soldeAcquis;
        $this->recalculerSolde();
        return $this;
    }

    public function getSoldePris(): string
    {
        return $this->soldePris;
    }

    public function setSoldePris(string $soldePris): self
    {
        $this->soldePris = $soldePris;
        $this->recalculerSolde();
        return $this;
    }

    public function getSolde(): ?string
    {
        return $this->solde;
    }

    public function setSolde(string $solde): self
    {
        $this->solde = $solde;

        return $this;
    }

    public function recalculerSolde(): void
    {
        $acquis = (float) $this->soldeAcquis;
        $pris = (float) $this->soldePris;
        $restant = max(0, $acquis - $pris);
        $this->solde = number_format($restant, 2, '.', '');
        $this->updatedAt = new \DateTime();
    }

    public function debiter(float $jours): void
    {
        $pris = (float) $this->soldePris + $jours;
        $this->soldePris = number_format($pris, 2, '.', '');
        $this->recalculerSolde();
    }

    public function crediter(float $jours): void
    {
        $pris = max(0, (float) $this->soldePris - $jours);
        $this->soldePris = number_format($pris, 2, '.', '');
        $this->recalculerSolde();
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
}