<?php

namespace App\MultiTenancy;

/**
 * Interface that must be implemented by any entity acting as a Tenant in Suivi des Carrières.
 */
interface TenantInterface
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public function getId(): ?int;

    public function getCode(): ?string;

    public function getName(): ?string;

    public function getStatus(): string;

    public function isActive(): bool;

    public function isSuspended(): bool;

    public function isArchived(): bool;
}
