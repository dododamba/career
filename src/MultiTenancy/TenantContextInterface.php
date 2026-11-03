<?php

namespace App\MultiTenancy;

use App\Entity\Institution;

interface TenantContextInterface
{
    /**
     * Gets the currently active Tenant (Institution).
     * Returns null if unauthenticated or no tenant is active.
     */
    public function getCurrentTenant(): ?Institution;

    /**
     * Alias for getCurrentTenant().
     */
    public function getTenant(): ?Institution;

    /**
     * Requires an active tenant. Throws TenantMissingException if none available.
     * Enforces FAIL CLOSED principle.
     */
    public function requireTenant(): Institution;

    /**
     * Checks if a tenant is currently resolved and active.
     */
    public function hasTenant(): bool;

    /**
     * Checks if the current context has global platform super-admin privileges.
     */
    public function isSuperAdminContext(): bool;

    /**
     * Sets or switches the current tenant (e.g. for super admin or authorized session).
     */
    public function setCurrentTenant(?Institution $tenant): void;

    /**
     * Executes a callback within a specific tenant context, restoring previous context afterwards.
     */
    public function executeInTenantContext(Institution $tenant, callable $callback): mixed;

    /**
     * Executes a callback with tenant filter disabled (for explicit, audited global admin tasks).
     */
    public function executeWithoutTenantFilter(callable $callback): mixed;
}
