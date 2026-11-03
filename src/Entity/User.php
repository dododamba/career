<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_user_institution')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà pris !')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface, BackupCodeInterface, TenantAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(name: 'totp_secret', length: 255, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(name: 'is_totp_enabled', type: 'boolean', options: ['default' => false])]
    private bool $isTotpEnabled = false;

    #[ORM\Column(name: 'backup_codes', type: 'json', options: ['default' => '[]'])]
    private array $backupCodes = [];

    #[ORM\Column(name: 'password_changed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordChangedAt = null;

    #[ORM\Column(name: 'last_login_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    // Relation ManyToOne vers Institution - Un utilisateur appartient à une institution
    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Institution $institution = null;

    // Relation OneToMany vers Employe - Un utilisateur peut avoir plusieurs employés (si nécessaire)
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Employe::class, cascade: ['persist', 'remove'])]
    private Collection $employes;

   


    public function __construct()
    {
        $this->employes = new ArrayCollection();
        $this->setSlug('uuid-' . uniqid()); // Utilisation d'uniqid() au lieu de str_randomize()
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getFullName(): string
    {
        return trim(($this->firstname ?? '') . ' ' . ($this->lastname ?? ''));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    // === RELATION INSTITUTION ===

    /**
     * @return Institution|null
     */
    public function getInstitution(): ?Institution
    {
        return $this->institution;
    }

    /**
     * @param Institution|null $institution
     * @return static
     */
    public function setInstitution(?Institution $institution): static
    {
        $this->institution = $institution;
        return $this;
    }

    /**
     * Vérifie si l'utilisateur appartient à une institution
     */
    public function hasInstitution(): bool
    {
        return $this->institution !== null;
    }

    /**
     * Obtient le nom de l'institution
     */
    public function getInstitutionName(): ?string
    {
        return $this->institution?->getNom();
    }

    public function getNomComplet(): string
    {
        $firstEmploye = $this->employes->first();
        if ($firstEmploye instanceof Employe) {
            return $firstEmploye->getNomComplet();
        }

        return $this->email;
    }

    // === RELATION EMPLOYES ===

    /**
     * @return Collection<int, Employe>
     */
    public function getEmployes(): Collection
    {
        return $this->employes;
    }

    public function addEmploye(Employe $employe): static
    {
        if (!$this->employes->contains($employe)) {
            $this->employes->add($employe);
            $employe->setUser($this);
        }

        return $this;
    }

    public function removeEmploye(Employe $employe): static
    {
        if ($this->employes->removeElement($employe)) {
            // set the owning side to null (unless already changed)
            if ($employe->getUser() === $this) {
                $employe->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Obtient le premier employé associé (pour compatibilité)
     */
    public function getEmploye(): ?Employe
    {
        return $this->employes->first() ?: null;
    }

    /**
     * Vérifie si l'utilisateur a au moins un employé associé
     */
    public function hasEmploye(): bool
    {
        return !$this->employes->isEmpty();
    }

    // === MÉTHODES UTILITAIRES ===

    /**
     * Retourne une représentation string de l'utilisateur
     */
    public function __toString(): string
    {
        return $this->getFullName() ?: $this->email ?: 'Utilisateur #' . $this->id;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * Vérifie si l'utilisateur est manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('ROLE_MANAGER');
    }

    /**
     * Obtient l'initiales de l'utilisateur pour l'avatar
     */
    public function getInitials(): string
    {
        $firstname = $this->firstname ? strtoupper(substr($this->firstname, 0, 1)) : '';
        $lastname = $this->lastname ? strtoupper(substr($this->lastname, 0, 1)) : '';
        
        return $firstname . $lastname ?: strtoupper(substr($this->email, 0, 2));
    }

    /**
     * Vérifie si l'utilisateur appartient à la même institution qu'un autre utilisateur
     */
    public function isSameInstitution(User $otherUser): bool
    {
        return $this->institution && $otherUser->getInstitution() 
            && $this->institution->getId() === $otherUser->getInstitution()->getId();
    }

    public function isActive(): bool
    {
        return $this->isVerified;
    }

    // === TOTP & 2FA METHODS ===

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->isTotpEnabled && !empty($this->totpSecret);
    }

    public function getTotpAuthenticationUsername(): string
    {
        return (string) $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if (!$this->totpSecret) {
            return null;
        }

        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): static
    {
        $this->totpSecret = $totpSecret;
        return $this;
    }

    public function isTotpEnabled(): bool
    {
        return $this->isTotpEnabled;
    }

    public function setIsTotpEnabled(bool $isTotpEnabled): static
    {
        $this->isTotpEnabled = $isTotpEnabled;
        return $this;
    }

    // === BACKUP CODES METHODS ===

    public function getBackupCodes(): array
    {
        return $this->backupCodes;
    }

    public function setBackupCodes(array $backupCodes): static
    {
        $this->backupCodes = $backupCodes;
        return $this;
    }

    public function isBackupCode(string $code): bool
    {
        $hashedCode = hash('sha256', trim($code));
        return in_array($hashedCode, $this->backupCodes, true);
    }

    public function invalidateBackupCode(string $code): void
    {
        $hashedCode = hash('sha256', trim($code));
        $key = array_search($hashedCode, $this->backupCodes, true);
        if ($key !== false) {
            unset($this->backupCodes[$key]);
            $this->backupCodes = array_values($this->backupCodes);
        }
    }

    // === SECURITY TIMESTAMPS ===

    public function getPasswordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    public function setPasswordChangedAt(?\DateTimeImmutable $passwordChangedAt): static
    {
        $this->passwordChangedAt = $passwordChangedAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }
}