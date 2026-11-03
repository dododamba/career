<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\OccupationPosteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OccupationPosteRepository::class)]
#[ORM\Table(name: 'occupation_poste')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_occ_poste_institution')]
#[ORM\Index(columns: ['poste_id'], name: 'idx_occ_poste_poste')]
#[ORM\Index(columns: ['employe_id'], name: 'idx_occ_poste_employe')]
#[ORM\Index(columns: ['is_actif'], name: 'idx_occ_poste_actif')]
class OccupationPoste implements TenantAwareInterface
{
    public const MOTIF_RECRUTEMENT = 'RECRUTEMENT';
    public const MOTIF_PROMOTION = 'PROMOTION';
    public const MOTIF_MUTATION = 'MUTATION';
    public const MOTIF_INTERIM = 'INTERIM';
    public const MOTIF_REORGANISATION = 'REORGANISATION';
    public const MOTIF_AUTRE = 'AUTRE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Poste::class, inversedBy: 'occupations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Poste $poste = null;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $motif = self::MOTIF_RECRUTEMENT;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $is_actif = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->date_debut = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->is_actif = true;
        $this->motif = self::MOTIF_RECRUTEMENT;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoste(): ?Poste
    {
        return $this->poste;
    }

    public function setPoste(?Poste $poste): static
    {
        $this->poste = $poste;
        return $this;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        $this->employe = $employe;
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
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeInterface $date_debut): static
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTimeInterface $date_fin): static
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->is_actif;
    }

    public function setIsActif(bool $is_actif): static
    {
        $this->is_actif = $is_actif;
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

    public function getDuree(): string
    {
        if (!$this->date_debut) {
            return 'N/C';
        }

        $end = $this->date_fin ?? new \DateTime();
        $diff = $this->date_debut->diff($end);

        if ($diff->y > 0) {
            return sprintf('%d an%s%s', $diff->y, $diff->y > 1 ? 's' : '', $diff->m > 0 ? sprintf(' %d m.', $diff->m) : '');
        } elseif ($diff->m > 0) {
            return sprintf('%d mois', $diff->m);
        } else {
            return sprintf('%d jour%s', $diff->d, $diff->d > 1 ? 's' : '');
        }
    }
}
