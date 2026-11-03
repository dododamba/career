<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\PointageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointageRepository::class)]
#[ORM\Table(name: 'pointage')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_pointage_institution')]
class Pointage implements TenantAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

   
    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $heure_arrivee = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $heure_depart = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $est_present = true;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $motif_absence = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $status = null;


    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateEntree = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateSortie = null;

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'pointages')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    private ?string $commentaire = null;
    
    public function __construct()
    {
        $this->date = new \DateTime();
        $this->dateEntree = new \DateTime();
        $this->setHeureArrivee(new \DateTime());
        $this->setStatus('Présent');
        $this->setEstPresent(true);
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heure_arrivee;
    }

    public function setHeureArrivee(?\DateTimeInterface $heure_arrivee): self
    {
        $this->heure_arrivee = $heure_arrivee;

        return $this;
    }

    public function getHeureDepart(): ?\DateTimeInterface
    {
        return $this->heure_depart;
    }

    public function setHeureDepart(?\DateTimeInterface $heure_depart): self
    {
        $this->heure_depart = $heure_depart;

        return $this;
    }

    public function isEstPresent(): ?bool
    {
        return $this->est_present;
    }

    public function getEstPresent(): ?bool
    {
        return $this->est_present;
    }

    public function setEstPresent(bool $est_present): self
    {
        $this->est_present = $est_present;

        return $this;
    }

    public function getMotifAbsence(): ?string
    {
        return $this->motif_absence;
    }

    public function setMotifAbsence(?string $motif_absence): self
    {
        $this->motif_absence = $motif_absence;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->dateEntree;
    }

    public function setDateEntree(\DateTimeInterface $dateEntree): self
    {
        $this->dateEntree = $dateEntree;
        return $this;
    }

    public function getDateSortie(): ?\DateTimeInterface
    {
        return $this->dateSortie;
    }

    public function setDateSortie(?\DateTimeInterface $dateSortie): self
    {
        $this->dateSortie = $dateSortie;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}