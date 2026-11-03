<?php

namespace App\Entity;

use App\Repository\MissionParticipantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MissionParticipantRepository::class)]
#[ORM\Table(name: 'mission_participant')]
#[ORM\Index(columns: ['role'], name: 'idx_mission_participant_role')]
class MissionParticipant
{
    public const ROLE_CHEF_MISSION = 'CHEF_MISSION';
    public const ROLE_PARTICIPANT = 'PARTICIPANT';
    public const ROLE_EXPERT = 'EXPERT';
    public const ROLE_CHAUFFEUR = 'CHAUFFEUR';
    public const ROLE_RAPPORTEUR = 'RAPPORTEUR';

    public const ROLES = [
        'Chef de mission' => self::ROLE_CHEF_MISSION,
        'Participant / Membre' => self::ROLE_PARTICIPANT,
        'Expert métier' => self::ROLE_EXPERT,
        'Chauffeur' => self::ROLE_CHAUFFEUR,
        'Rapporteur' => self::ROLE_RAPPORTEUR,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Mission $mission = null;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Employe $employe = null;

    #[ORM\Column(length: 50, options: ['default' => self::ROLE_PARTICIPANT])]
    private string $role = self::ROLE_PARTICIPANT;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $indemniteJournaliere = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $fraisTransport = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalFrais = null;

    #[ORM\ManyToOne(targetEntity: Acte::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Acte $acteIndividuel = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->role = self::ROLE_PARTICIPANT;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function setMission(?Mission $mission): self
    {
        $this->mission = $mission;

        return $this;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): self
    {
        $this->employe = $employe;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRoleLabel(): string
    {
        return array_search($this->role, self::ROLES, true) ?: $this->role;
    }

    public function getIndemniteJournaliere(): ?string
    {
        return $this->indemniteJournaliere;
    }

    public function getIndemnitesMission(): ?string
    {
        return $this->indemniteJournaliere;
    }

    public function setIndemniteJournaliere(?string $indemniteJournaliere): self
    {
        $this->indemniteJournaliere = $indemniteJournaliere;

        return $this;
    }

    public function getFraisTransport(): ?string
    {
        return $this->fraisTransport;
    }

    public function setFraisTransport(?string $fraisTransport): self
    {
        $this->fraisTransport = $fraisTransport;

        return $this;
    }

    public function getTotalFrais(): ?string
    {
        return $this->totalFrais;
    }

    public function setTotalFrais(?string $totalFrais): self
    {
        $this->totalFrais = $totalFrais;

        return $this;
    }

    public function getActeIndividuel(): ?Acte
    {
        return $this->acteIndividuel;
    }

    public function setActeIndividuel(?Acte $acteIndividuel): self
    {
        $this->acteIndividuel = $acteIndividuel;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
