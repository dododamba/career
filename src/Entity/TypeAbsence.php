<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\TypeAbsenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeAbsenceRepository::class)]
#[ORM\Table(name: 'type_absence')]
#[ORM\Index(columns: ['code'], name: 'idx_type_absence_code')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_type_absence_institution')]
class TypeAbsence implements TenantAwareInterface
{
    public const CODE_CONCOURS_EXAMEN = 'CONCOURS_EXAMEN';
    public const CODE_CONSULTATION_MEDICALE = 'CONSULTATION_MEDICALE';
    public const CODE_EVENEMENT_FAMILIAL = 'EVENEMENT_FAMILIAL';
    public const CODE_CONVENANCE_PERSONNELLE = 'CONVENANCE_PERSONNELLE';
    public const CODE_FORMATION_COURTE = 'FORMATION_COURTE';
    public const CODE_ACTIVITE_SYNDICALE = 'ACTIVITE_SYNDICALE';
    public const CODE_AUTRE = 'AUTRE';

    public const UNITE_JOURS = 'JOURS';
    public const UNITE_HEURES = 'HEURES';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $code = self::CODE_CONVENANCE_PERSONNELLE;

    #[ORM\Column(length: 150)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, options: ['default' => 'JOURS'])]
    private string $unite = self::UNITE_JOURS;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $dureeMax = '3.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $quotaAnnuelMax = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $justificatifRequis = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $remuneree = true;

    #[ORM\Column(length: 7, nullable: true, options: ['default' => '#17a2b8'])]
    private ?string $couleur = '#17a2b8';

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $actif = true;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    /**
     * @var Collection<int, Absence>
     */
    #[ORM\OneToMany(mappedBy: 'typeAbsence', targetEntity: Absence::class)]
    private Collection $absences;

    public function __construct()
    {
        $this->absences = new ArrayCollection();
        $this->actif = true;
        $this->remuneree = true;
        $this->justificatifRequis = false;
        $this->unite = self::UNITE_JOURS;
        $this->couleur = '#17a2b8';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper(trim($code));

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = trim($libelle);

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

    public function getUnite(): string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): self
    {
        $this->unite = in_array(strtoupper($unite), [self::UNITE_JOURS, self::UNITE_HEURES], true)
            ? strtoupper($unite)
            : self::UNITE_JOURS;

        return $this;
    }

    public function getDureeMax(): ?string
    {
        return $this->dureeMax;
    }

    public function setDureeMax(?string $dureeMax): self
    {
        $this->dureeMax = $dureeMax;

        return $this;
    }

    public function getQuotaAnnuelMax(): ?string
    {
        return $this->quotaAnnuelMax;
    }

    public function setQuotaAnnuelMax(?string $quotaAnnuelMax): self
    {
        $this->quotaAnnuelMax = $quotaAnnuelMax;

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

    public function isRemuneree(): bool
    {
        return $this->remuneree;
    }

    public function setRemuneree(bool $remuneree): self
    {
        $this->remuneree = $remuneree;

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

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
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
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(Absence $absence): self
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setTypeAbsence($this);
        }

        return $this;
    }

    public function removeAbsence(Absence $absence): self
    {
        if ($this->absences->removeElement($absence)) {
            if ($absence->getTypeAbsence() === $this) {
                $absence->setTypeAbsence(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? $this->code ?? '';
    }
}
