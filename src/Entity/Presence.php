<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\PresenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PresenceRepository::class)]
#[ORM\Table(name: 'presence')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_presence_institution')]
class Presence implements TenantAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_presence = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heure_entree = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heure_sortie = null;

    #[ORM\Column(nullable: true)]
    private ?bool $statut = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatePresence(): ?\DateTimeInterface
    {
        return $this->date_presence;
    }

    public function setDatePresence(?\DateTimeInterface $date_presence): static
    {
        $this->date_presence = $date_presence;

        return $this;
    }

    public function getHeureEntree(): ?\DateTimeInterface
    {
        return $this->heure_entree;
    }

    public function setHeureEntree(?\DateTimeInterface $heure_entree): static
    {
        $this->heure_entree = $heure_entree;

        return $this;
    }

    public function getHeureSortie(): ?\DateTimeInterface
    {
        return $this->heure_sortie;
    }

    public function setHeureSortie(?\DateTimeInterface $heure_sortie): static
    {
        $this->heure_sortie = $heure_sortie;

        return $this;
    }

    public function isStatut(): ?bool
    {
        return $this->statut;
    }

    public function setStatut(?bool $statut): static
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
}
