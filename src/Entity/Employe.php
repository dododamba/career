<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\EmployeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
#[ORM\Table(name: 'employe')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_employe_institution')]
#[ORM\Index(columns: ['structure_id'], name: 'idx_employe_structure')]
#[ORM\Index(columns: ['poste_id'], name: 'idx_employe_poste')]
#[ORM\Index(columns: ['matricule'], name: 'idx_employe_matricule')]
class Employe implements TenantAwareInterface
{
    public const STATUT_ADMIN_FONCTIONNAIRE = 'FONCTIONNAIRE';
    public const STATUT_ADMIN_CONTRACTUEL = 'CONTRACTUEL';
    public const STATUT_ADMIN_STAGIAIRE = 'STAGIAIRE';
    public const STATUT_ADMIN_DETACHE = 'DETACHE';
    public const STATUT_ADMIN_AUTRE = 'AUTRE';

    public const SITUATION_EN_ACTIVITE = 'EN_ACTIVITE';
    public const SITUATION_DETACHEMENT = 'DETACHEMENT';
    public const SITUATION_DISPONIBILITE = 'DISPONIBILITE';
    public const SITUATION_SUSPENSION = 'SUSPENSION';
    public const SITUATION_RETRAITE = 'RETRAITE';
    public const SITUATION_RADIE = 'RADIE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_naissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu_naissance = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nationalite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numero_telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?float $salaire = null;

    #[ORM\Column(nullable: true)]
    private ?bool $statut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_embauche = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_prise_service = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statut_administratif = self::STATUT_ADMIN_CONTRACTUEL;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $corps = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $echelon = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $regime = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $situation_administrative = self::SITUATION_EN_ACTIVITE;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $matricule = null;

    // Relation vers User au lieu d'Institution directement
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'employes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'employes')]
    private ?Fonction $fonction = null;

    #[ORM\ManyToOne(targetEntity: Structure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $structure = null;

    #[ORM\ManyToOne(targetEntity: Poste::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Poste $poste = null;

    #[ORM\ManyToOne(inversedBy: 'employes')]
    private ?Service $service = null;

    #[ORM\Column(nullable: true)]
    private ?int $numero = null;

    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Conges::class)]
    private Collection $conges;

    /**
     * @var Collection<int, Absence>
     */
    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Absence::class)]
    private Collection $absences;

    /**
     * @var Collection<int, SoldeConge>
     */
    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: SoldeConge::class, orphanRemoval: true)]
    private Collection $soldesConges;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;
  
    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Visite::class, orphanRemoval: true)]
    private Collection $visitesProgrammees;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $actif = true;

    #[ORM\OneToMany(targetEntity: Pointage::class, mappedBy: 'employe', orphanRemoval: true)]
    private Collection $pointages;

    /**
     * @var Collection<int, Affectation>
     */
    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Affectation::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['date_effet' => 'DESC', 'id' => 'DESC'])]
    private Collection $affectations;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Document::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $documents;

    #[ORM\ManyToOne(inversedBy: 'employes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Institution $institution = null;

    public function __construct()
    {
        $this->pointages = new ArrayCollection();
        $this->visitesProgrammees = new ArrayCollection();
        $this->conges = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->soldesConges = new ArrayCollection();
        $this->affectations = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(?\DateTimeInterface $date_naissance): static
    {
        $this->date_naissance = $date_naissance;
        return $this;
    }

    public function getNumeroTelephone(): ?string
    {
        return $this->numero_telephone;
    }

    public function setNumeroTelephone(?string $numero_telephone): static
    {
        $this->numero_telephone = $numero_telephone;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->numero_telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->numero_telephone = $telephone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getSalaire(): ?float
    {
        return $this->salaire;
    }

    public function setSalaire(float $salaire): static
    {
        $this->salaire = $salaire;
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

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->date_embauche;
    }

    public function setDateEmbauche(?\DateTimeInterface $date_embauche): static
    {
        $this->date_embauche = $date_embauche;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;
        return $this;
    }

    // Nouveau getter/setter pour User
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        if ($user !== null && $this->institution === null && $user->getInstitution() !== null) {
            $this->institution = $user->getInstitution();
        }
        return $this;
    }

    public function getInstitution(): ?Institution
    {
        return $this->institution ?? $this->user?->getInstitution();
    }

    public function setInstitution(?Institution $institution): static
    {
        $this->institution = $institution;

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

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): static
    {
        $this->numero = $numero;
        return $this;
    }

    /**
     * @return Collection<int, Conges>
     */
    public function getConges(): Collection
    {
        return $this->conges;
    }

    public function addConge(Conges $conge): static
    {
        if (!$this->conges->contains($conge)) {
            $this->conges->add($conge);
            $conge->setEmploye($this);
        }
        return $this;
    }

    public function removeConge(Conges $conge): static
    {
        if ($this->conges->removeElement($conge)) {
            if ($conge->getEmploye() === $this) {
                $conge->setEmploye(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(Absence $absence): static
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setEmploye($this);
        }
        return $this;
    }

    public function removeAbsence(Absence $absence): static
    {
        if ($this->absences->removeElement($absence)) {
            if ($absence->getEmploye() === $this) {
                $absence->setEmploye(null);
            }
        }
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(?bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    /**
     * @return Collection<int, Pointage>
     */
    public function getPointages(): Collection
    {
        return $this->pointages;
    }

    public function addPointage(Pointage $pointage): self
    {
        if (!$this->pointages->contains($pointage)) {
            $this->pointages->add($pointage);
            $pointage->setEmploye($this);
        }
        return $this;
    }

    public function removePointage(Pointage $pointage): self
    {
        if ($this->pointages->removeElement($pointage)) {
            if ($pointage->getEmploye() === $this) {
                $pointage->setEmploye(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Visite>
     */
    public function getVisitesProgrammees(): Collection
    {
        return $this->visitesProgrammees;
    }

    public function addVisitesProgrammee(Visite $visite): static
    {
        if (!$this->visitesProgrammees->contains($visite)) {
            $this->visitesProgrammees->add($visite);
            $visite->setEmploye($this);
        }
        return $this;
    }

    public function removeVisitesProgrammee(Visite $visite): static
    {
        if ($this->visitesProgrammees->removeElement($visite)) {
            if ($visite->getEmploye() === $this) {
                $visite->setEmploye(null);
            }
        }
        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieu_naissance;
    }

    public function setLieuNaissance(?string $lieu_naissance): static
    {
        $this->lieu_naissance = $lieu_naissance;
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;
        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(?string $nationalite): static
    {
        $this->nationalite = $nationalite;
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

    public function getPoste(): ?Poste
    {
        return $this->poste;
    }

    public function setPoste(?Poste $poste): static
    {
        $this->poste = $poste;
        return $this;
    }

    public function getPosteActuel(): ?Poste
    {
        return $this->poste;
    }

    public function setPosteActuel(?Poste $poste): static
    {
        return $this->setPoste($poste);
    }

    public function getDatePriseService(): ?\DateTimeInterface
    {
        return $this->date_prise_service;
    }

    public function setDatePriseService(?\DateTimeInterface $date_prise_service): static
    {
        $this->date_prise_service = $date_prise_service;
        return $this;
    }

    public function getStatutAdministratif(): ?string
    {
        return $this->statut_administratif;
    }

    public function setStatutAdministratif(?string $statut_administratif): static
    {
        $this->statut_administratif = $statut_administratif;
        return $this;
    }

    public function getCorps(): ?string
    {
        return $this->corps;
    }

    public function setCorps(?string $corps): static
    {
        $this->corps = $corps;
        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getEchelon(): ?string
    {
        return $this->echelon;
    }

    public function setEchelon(?string $echelon): static
    {
        $this->echelon = $echelon;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getRegime(): ?string
    {
        return $this->regime;
    }

    public function setRegime(?string $regime): static
    {
        $this->regime = $regime;
        return $this;
    }

    public function getSituationAdministrative(): ?string
    {
        return $this->situation_administrative;
    }

    public function setSituationAdministrative(?string $situation_administrative): static
    {
        $this->situation_administrative = $situation_administrative;
        return $this;
    }

    // Méthode pour faciliter l'affichage du nom complet
    public function getNomComplet(): string
    {
        return ($this->getNom() ?? '') . ' ' . ($this->getPrenom() ?? '');
    }

    /**
     * @return Collection<int, SoldeConge>
     */
    public function getSoldesConges(): Collection
    {
        return $this->soldesConges;
    }

    public function addSoldeConge(SoldeConge $soldeConge): static
    {
        if (!$this->soldesConges->contains($soldeConge)) {
            $this->soldesConges->add($soldeConge);
            $soldeConge->setEmploye($this);
        }

        return $this;
    }

    public function removeSoldeConge(SoldeConge $soldeConge): static
    {
        if ($this->soldesConges->removeElement($soldeConge)) {
            if ($soldeConge->getEmploye() === $this) {
                $soldeConge->setEmploye(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Affectation>
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function addAffectation(Affectation $affectation): static
    {
        if (!$this->affectations->contains($affectation)) {
            $this->affectations->add($affectation);
            $affectation->setEmploye($this);
        }

        return $this;
    }

    public function removeAffectation(Affectation $affectation): static
    {
        if ($this->affectations->removeElement($affectation)) {
            if ($affectation->getEmploye() === $this) {
                $affectation->setEmploye(null);
            }
        }

        return $this;
    }

    public function getAffectationActive(): ?Affectation
    {
        foreach ($this->affectations as $aff) {
            if ($aff->isActive()) {
                return $aff;
            }
        }
        return null;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setEmploye($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getEmploye() === $this) {
                $document->setEmploye(null);
            }
        }

        return $this;
    }
}