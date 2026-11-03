<?php

namespace App\Entity;

use App\MultiTenancy\TenantAwareInterface;
use App\Repository\SecurityAuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SecurityAuditLogRepository::class)]
#[ORM\Table(name: 'security_audit_log')]
#[ORM\Index(columns: ['event'], name: 'idx_security_audit_log_event')]
#[ORM\Index(columns: ['created_at'], name: 'idx_security_audit_log_created_at')]
#[ORM\Index(columns: ['user_identifier'], name: 'idx_security_audit_log_user_identifier')]
#[ORM\Index(columns: ['institution_id'], name: 'idx_security_audit_log_institution')]
class SecurityAuditLog implements TenantAwareInterface
{
    public const EVENT_LOGIN_SUCCESS = 'LOGIN_SUCCESS';
    public const EVENT_LOGIN_FAILURE = 'LOGIN_FAILURE';
    public const EVENT_LOGOUT = 'LOGOUT';
    public const EVENT_PASSWORD_CHANGED = 'PASSWORD_CHANGED';
    public const EVENT_PASSWORD_RESET_REQUESTED = 'PASSWORD_RESET_REQUESTED';
    public const EVENT_PASSWORD_RESET_COMPLETED = 'PASSWORD_RESET_COMPLETED';
    public const EVENT_MFA_ENABLED = 'MFA_ENABLED';
    public const EVENT_MFA_DISABLED = 'MFA_DISABLED';
    public const EVENT_MFA_SUCCESS = 'MFA_SUCCESS';
    public const EVENT_MFA_FAILURE = 'MFA_FAILURE';
    public const EVENT_SESSION_REVOKED = 'SESSION_REVOKED';
    public const EVENT_ROLE_CHANGED = 'ROLE_CHANGED';
    public const EVENT_PERMISSION_CHANGED = 'PERMISSION_CHANGED';
    public const EVENT_ACCOUNT_DISABLED = 'ACCOUNT_DISABLED';
    public const EVENT_ACCOUNT_ENABLED = 'ACCOUNT_ENABLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Institution $institution = null;

    #[ORM\Column(name: 'user_identifier', length: 180)]
    private ?string $userIdentifier = null;

    #[ORM\Column(length: 50)]
    private ?string $event = null;

    #[ORM\Column(name: 'event_label', length: 100)]
    private ?string $eventLabel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'ip_address', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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
        return $this->institution;
    }

    public function setInstitution(?Institution $institution): static
    {
        $this->institution = $institution;

        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier): static
    {
        $this->userIdentifier = $userIdentifier;
        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(string $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getEventLabel(): ?string
    {
        return $this->eventLabel;
    }

    public function setEventLabel(string $eventLabel): static
    {
        $this->eventLabel = $eventLabel;
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

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        // Truncate to 255 if longer
        $this->userAgent = $userAgent ? substr($userAgent, 0, 255) : null;
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
}
