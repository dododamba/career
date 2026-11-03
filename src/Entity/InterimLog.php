<?php

namespace App\Entity;

use App\Repository\InterimLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterimLogRepository::class)]
#[ORM\Table(name: 'interim_logs')]
#[ORM\Index(columns: ['interim_id'], name: 'idx_interim_log_interim')]
#[ORM\Index(columns: ['action'], name: 'idx_interim_log_action')]
class InterimLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Interim::class, inversedBy: 'interimLogs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Interim $interim = null;

    #[ORM\Column(length: 50)]
    private string $action;

    #[ORM\Column(length: 255)]
    private string $actionLabel;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInterim(): ?Interim
    {
        return $this->interim;
    }

    public function setInterim(?Interim $interim): self
    {
        $this->interim = $interim;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getActionLabel(): string
    {
        return $this->actionLabel;
    }

    public function setActionLabel(string $actionLabel): self
    {
        $this->actionLabel = $actionLabel;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->actionLabel;
    }

    public function getUtilisateur(): ?User
    {
        return $this->user;
    }
}
