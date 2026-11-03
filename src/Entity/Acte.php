<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\ActeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActeRepository::class)]
#[ORM\Table(name: 'acte')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_acte_institution')]
#[ORM\Index(columns: ['type_acte_id'], name: 'idx_acte_type')]
#[ORM\Index(columns: ['employe_id'], name: 'idx_acte_employe')]
#[ORM\Index(columns: ['poste_id'], name: 'idx_acte_poste')]
#[ORM\Index(columns: ['structure_id'], name: 'idx_acte_structure')]
#[ORM\Index(columns: ['affectation_id'], name: 'idx_acte_affectation')]
#[ORM\Index(columns: ['statut'], name: 'idx_acte_statut')]
#[ORM\Index(columns: ['numero'], name: 'idx_acte_numero')]
class Acte implements TenantAwareInterface
{
    public const STATUT_PROJET = 'PROJET';
    public const STATUT_SIGNE = 'SIGNE';
    public const STATUT_NOTIFIE = 'NOTIFIE';
    public const STATUT_MODIFIE = 'MODIFIE';
    public const STATUT_ABROGE = 'ABROGE';
    public const STATUT_ANNULE = 'ANNULE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $numero = null;

    #[ORM\ManyToOne(targetEntity: TypeActe::class, inversedBy: 'actes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?TypeActe $type_acte = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Institution $institution = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_signature = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_effet = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_publication = null;

    #[ORM\Column(length: 255)]
    private ?string $objet = null;

    #[ORM\Column(length: 255)]
    private ?string $signataire_qualite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signataire_nom = null;

    #[ORM\Column(length: 50)]
    private string $statut = self::STATUT_SIGNE;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu_resume = null;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Document $document = null;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Employe $employe = null;

    #[ORM\ManyToOne(targetEntity: Poste::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Poste $poste = null;

    #[ORM\ManyToOne(targetEntity: Structure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $structure = null;

    #[ORM\ManyToOne(targetEntity: Affectation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Affectation $affectation = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'actes_modificatifs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $acte_parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'acte_parent', targetEntity: self::class)]
    private Collection $actes_modificatifs;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $cree_par = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->date_signature = new \DateTime();
        $this->date_effet = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->statut = self::STATUT_SIGNE;
        $this->actes_modificatifs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;
        return $this;
    }

    public function getTypeActe(): ?TypeActe
    {
        return $this->type_acte;
    }

    public function setTypeActe(?TypeActe $type_acte): static
    {
        $this->type_acte = $type_acte;
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

    public function getDateSignature(): ?\DateTimeInterface
    {
        return $this->date_signature;
    }

    public function setDateSignature(\DateTimeInterface $date_signature): static
    {
        $this->date_signature = $date_signature;
        return $this;
    }

    public function getDateEffet(): ?\DateTimeInterface
    {
        return $this->date_effet;
    }

    public function setDateEffet(\DateTimeInterface $date_effet): static
    {
        $this->date_effet = $date_effet;
        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->date_publication;
    }

    public function setDatePublication(?\DateTimeInterface $date_publication): static
    {
        $this->date_publication = $date_publication;
        return $this;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): static
    {
        $this->objet = $objet;
        return $this;
    }

    public function getSignataireQualite(): ?string
    {
        return $this->signataire_qualite;
    }

    public function setSignataireQualite(string $signataire_qualite): static
    {
        $this->signataire_qualite = $signataire_qualite;
        return $this;
    }

    public function getSignataireNom(): ?string
    {
        return $this->signataire_nom;
    }

    public function setSignataireNom(?string $signataire_nom): static
    {
        $this->signataire_nom = $signataire_nom;
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

    public function getContenuResume(): ?string
    {
        return $this->contenu_resume;
    }

    public function setContenuResume(?string $contenu_resume): static
    {
        $this->contenu_resume = $contenu_resume;
        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;
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

    public function getPoste(): ?Poste
    {
        return $this->poste;
    }

    public function setPoste(?Poste $poste): static
    {
        $this->poste = $poste;
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

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }

    public function setAffectation(?Affectation $affectation): static
    {
        $this->affectation = $affectation;
        return $this;
    }

    public function getActeParent(): ?self
    {
        return $this->acte_parent;
    }

    public function setActeParent(?self $acte_parent): static
    {
        $this->acte_parent = $acte_parent;
        if ($acte_parent !== null && !$acte_parent->getActesModificatifs()->contains($this)) {
            $acte_parent->getActesModificatifs()->add($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getActesModificatifs(): Collection
    {
        return $this->actes_modificatifs;
    }

    public function addActesModificatif(self $actesModificatif): static
    {
        if (!$this->actes_modificatifs->contains($actesModificatif)) {
            $this->actes_modificatifs->add($actesModificatif);
            $actesModificatif->setActeParent($this);
        }
        return $this;
    }

    public function removeActesModificatif(self $actesModificatif): static
    {
        if ($this->actes_modificatifs->removeElement($actesModificatif)) {
            if ($actesModificatif->getActeParent() === $this) {
                $actesModificatif->setActeParent(null);
            }
        }
        return $this;
    }

    public function getCreePar(): ?User
    {
        return $this->cree_par;
    }

    public function setCreePar(?User $cree_par): static
    {
        $this->cree_par = $cree_par;
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

    public function getStatutLibelle(): string
    {
        return match ($this->statut) {
            self::STATUT_PROJET => 'Projet',
            self::STATUT_SIGNE => 'Signé',
            self::STATUT_NOTIFIE => 'Notifié / Publié',
            self::STATUT_MODIFIE => 'Modifié',
            self::STATUT_ABROGE => 'Abrogé',
            self::STATUT_ANNULE => 'Annulé',
            default => $this->statut,
        };
    }

    public function getStatutLabel(): string
    {
        return $this->getStatutLibelle();
    }

    public function getStatutBadgeClass(): string
    {
        return match ($this->statut) {
            self::STATUT_SIGNE => 'success',
            self::STATUT_NOTIFIE => 'primary',
            self::STATUT_PROJET => 'warning text-dark',
            self::STATUT_MODIFIE => 'info',
            self::STATUT_ABROGE => 'secondary',
            self::STATUT_ANNULE => 'danger',
            default => 'light text-dark border',
        };
    }

    public function isEnVigueur(): bool
    {
        return in_array($this->statut, [self::STATUT_SIGNE, self::STATUT_NOTIFIE], true);
    }
}
