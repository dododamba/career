<?php

namespace App\MultiTenancy;

use App\Entity\Institution;

/**
 * Interface implemented by entities that belong to a Tenant (Institution).
 */
interface TenantAwareInterface
{
    public function getInstitution(): ?Institution;

    public function setInstitution(?Institution $institution): static;
}
