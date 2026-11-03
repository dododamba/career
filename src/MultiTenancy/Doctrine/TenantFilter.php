<?php

namespace App\MultiTenancy\Doctrine;

use App\Entity\Conges;
use App\Entity\Employe;
use App\Entity\Fonction;
use App\Entity\OrganisationHistory;
use App\Entity\Pointage;
use App\Entity\Presence;
use App\Entity\SecurityAuditLog;
use App\Entity\Service;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Visite;
use App\MultiTenancy\TenantAwareInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class TenantFilter extends SQLFilter
{
    /**
     * List of tenant-aware entities that must be strictly partitioned by institution_id.
     */
    private const TENANT_AWARE_CLASSES = [
        Employe::class,
        Service::class,
        Structure::class,
        OrganisationHistory::class,
        \App\Entity\Poste::class,
        \App\Entity\OccupationPoste::class,
        \App\Entity\Affectation::class,
        \App\Entity\Document::class,
        \App\Entity\DocumentVersion::class,
        \App\Entity\Acte::class,
        \App\Entity\SoldeConge::class,
        Pointage::class,
        Conges::class,
        \App\Entity\Absence::class,
        \App\Entity\Mission::class,
        \App\Entity\Interim::class,
        Visite::class,
        Presence::class,
        SecurityAuditLog::class,
    ];

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // 0. User is the platform-wide identity/auth entity (auth & reset are global, tenant isolated via UserVoter)
        if ($targetEntity->getName() === User::class) {
            return '';
        }
        // 1. Hybrid entities: Global or Tenant-scoped (e.g. Fonction, TypeActe, TypeConges, TypeAbsence)
        if ($targetEntity->getName() === Fonction::class || $targetEntity->getName() === \App\Entity\TypeActe::class || $targetEntity->getName() === \App\Entity\TypeConges::class || $targetEntity->getName() === \App\Entity\TypeAbsence::class) {
            try {
                $tenantId = $this->getParameter('tenant_id');
                $tenantId = trim($tenantId, "'");
            } catch (\InvalidArgumentException) {
                $tenantId = null;
            }

            if ($tenantId === null || $tenantId === '' || $tenantId === '-1') {
                // Show only global functions when no tenant is set
                return sprintf('%s.institution_id IS NULL', $targetTableAlias);
            }

            return sprintf('(%s.institution_id = %s OR %s.institution_id IS NULL)', $targetTableAlias, (int) $tenantId, $targetTableAlias);
        }

        // 2. Entities strictly scoped to a tenant
        if (in_array($targetEntity->getName(), self::TENANT_AWARE_CLASSES, true) || $targetEntity->reflClass?->implementsInterface(TenantAwareInterface::class)) {
            try {
                $tenantId = $this->getParameter('tenant_id');
            } catch (\InvalidArgumentException) {
                // FAIL CLOSED: If the parameter has not been set, deny all rows
                return '1 = 0';
            }

            // Remove any quoting added by Doctrine getParameter if present
            $tenantId = trim($tenantId, "'");

            if ($tenantId === '' || $tenantId === '-1') {
                // FAIL CLOSED: No tenant context active, return 0 rows
                return '1 = 0';
            }

            return sprintf('%s.institution_id = %s', $targetTableAlias, (int) $tenantId);
        }

        return '';
    }
}
