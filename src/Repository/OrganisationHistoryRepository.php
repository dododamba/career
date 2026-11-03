<?php

namespace App\Repository;

use App\Entity\Institution;
use App\Entity\OrganisationHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganisationHistory>
 */
class OrganisationHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganisationHistory::class);
    }

    /**
     * @return OrganisationHistory[]
     */
    public function findByInstitution(Institution $institution, int $limit = 50): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.changedBy', 'u')->addSelect('u')
            ->andWhere('h.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return OrganisationHistory[]
     */
    public function findByEntity(Institution $institution, string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.changedBy', 'u')->addSelect('u')
            ->andWhere('h.institution = :institution')
            ->andWhere('h.entityType = :entityType')
            ->andWhere('h.entityId = :entityId')
            ->setParameter('institution', $institution)
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
