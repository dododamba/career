<?php

namespace App\Repository;

use App\Entity\SecurityAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SecurityAuditLog>
 */
class SecurityAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityAuditLog::class);
    }

    /**
     * @return SecurityAuditLog[]
     */
    public function findLatest(int $limit = 50): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->addSelect('u')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SecurityAuditLog[]
     */
    public function findByUserIdentifier(string $identifier, int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.userIdentifier = :identifier')
            ->setParameter('identifier', $identifier)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
