<?php

namespace App\Repository;

use App\Entity\Institution;
use App\Entity\Structure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Structure>
 */
class StructureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Structure::class);
    }

    /**
     * @return Structure[]
     */
    public function findByInstitution(Institution $institution, bool $activeOnly = false): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('s.nom', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('s.statut = :active')
               ->setParameter('active', Structure::STATUT_ACTIVE);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Structure[]
     */
    public function findRootStructures(Institution $institution, bool $activeOnly = false): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.responsable', 'r')->addSelect('r')
            ->leftJoin('s.children', 'c')->addSelect('c')
            ->leftJoin('s.services', 'srv')->addSelect('srv')
            ->andWhere('s.institution = :institution')
            ->andWhere('s.parent IS NULL')
            ->setParameter('institution', $institution)
            ->orderBy('s.nom', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('s.statut = :active')
               ->setParameter('active', Structure::STATUT_ACTIVE);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Charge toutes les structures d'une institution avec leurs relations pour l'organigramme
     * @return Structure[]
     */
    public function findTreeByInstitution(Institution $institution): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.parent', 'p')->addSelect('p')
            ->leftJoin('s.children', 'c')->addSelect('c')
            ->leftJoin('s.services', 'srv')->addSelect('srv')
            ->leftJoin('s.responsable', 'r')->addSelect('r')
            ->leftJoin('srv.responsable', 'srvr')->addSelect('srvr')
            ->leftJoin('srv.employes', 'srve')->addSelect('srve')
            ->andWhere('s.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByInstitution(Institution $institution): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<string, int>
     */
    public function countByType(Institution $institution): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.type, COUNT(s.id) as count')
            ->andWhere('s.institution = :institution')
            ->setParameter('institution', $institution)
            ->groupBy('s.type')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['type']] = (int) $row['count'];
        }

        return $result;
    }
}
