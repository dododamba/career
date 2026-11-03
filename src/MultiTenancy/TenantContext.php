<?php

namespace App\MultiTenancy;

use App\Entity\Institution;
use App\Entity\User;
use App\MultiTenancy\Exception\TenantMissingException;
use App\MultiTenancy\Exception\TenantSuspendedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class TenantContext implements TenantContextInterface
{
    private ?Institution $currentTenant = null;
    private bool $isExplicitlySet = false;
    private bool $filterDisabled = false;

    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function getCurrentTenant(): ?Institution
    {
        if ($this->isExplicitlySet) {
            return $this->currentTenant;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $institution = $user->getInstitution();

        if ($institution !== null) {
            // Verify tenant is active
            if (!$institution->isActive() && !$this->isSuperAdminContext()) {
                throw new TenantSuspendedException(sprintf(
                    'L\'institution "%s" est actuellement %s. L\'accès est restreint.',
                    $institution->getNom(),
                    $institution->getStatus() === TenantInterface::STATUS_SUSPENDED ? 'suspendue' : 'archivée'
                ));
            }

            $this->currentTenant = $institution;
            return $institution;
        }

        // Super-admin without a fixed institution
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->currentTenant;
        }

        return null;
    }

    public function getTenant(): ?Institution
    {
        return $this->getCurrentTenant();
    }

    public function requireTenant(): Institution
    {
        $tenant = $this->getCurrentTenant();
        if ($tenant === null) {
            throw new TenantMissingException('Opération impossible : aucun contexte d\'institution actif.');
        }

        return $tenant;
    }

    public function hasTenant(): bool
    {
        try {
            return $this->getCurrentTenant() !== null;
        } catch (TenantSuspendedException) {
            return false;
        }
    }

    public function isSuperAdminContext(): bool
    {
        return $this->security->isGranted('ROLE_SUPER_ADMIN');
    }

    public function setCurrentTenant(?Institution $tenant): void
    {
        $this->currentTenant = $tenant;
        $this->isExplicitlySet = true;

        $this->applyDoctrineFilter();
    }

    public function applyDoctrineFilter(): void
    {
        if ($this->filterDisabled) {
            return;
        }

        $filters = $this->entityManager->getFilters();
        if (!$filters->has('tenant_filter')) {
            return;
        }

        $tenant = $this->getCurrentTenant();

        // If super admin and no tenant selected, we allow global view by disabling the filter
        if ($tenant === null && $this->isSuperAdminContext()) {
            if ($filters->isEnabled('tenant_filter')) {
                $filters->disable('tenant_filter');
            }
            return;
        }

        if ($filters->isEnabled('tenant_filter')) {
            $filters->disable('tenant_filter');
        }

        $filter = $filters->enable('tenant_filter');
        if ($tenant !== null && $tenant->getId() !== null) {
            $filter->setParameter('tenant_id', $tenant->getId());
        } else {
            // FAIL CLOSED: If no tenant is available, pass -1 (non-existent id)
            $filter->setParameter('tenant_id', -1);
        }
    }

    public function executeInTenantContext(Institution $tenant, callable $callback): mixed
    {
        $previousTenant = $this->currentTenant;
        $previousExplicit = $this->isExplicitlySet;

        try {
            $this->setCurrentTenant($tenant);
            return $callback();
        } finally {
            $this->currentTenant = $previousTenant;
            $this->isExplicitlySet = $previousExplicit;
            $this->applyDoctrineFilter();
        }
    }

    public function executeWithoutTenantFilter(callable $callback): mixed
    {
        $previousFilterDisabled = $this->filterDisabled;
        $this->filterDisabled = true;

        $filters = $this->entityManager->getFilters();
        $wasEnabled = $filters->has('tenant_filter') && $filters->isEnabled('tenant_filter');

        if ($wasEnabled) {
            $filters->disable('tenant_filter');
        }

        try {
            return $callback();
        } finally {
            $this->filterDisabled = $previousFilterDisabled;
            if ($wasEnabled && !$this->filterDisabled) {
                $this->applyDoctrineFilter();
            }
        }
    }
}
