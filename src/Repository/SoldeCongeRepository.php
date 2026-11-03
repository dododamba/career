<?php

namespace App\Repository;

use App\Entity\SoldeConge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SoldeConge>
 */
class SoldeCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoldeConge::class);
    }

    public function findOneByEmployeAndTypeAndAnnee(\App\Entity\Employe $employe, \App\Entity\TypeConges $type, int $annee): ?SoldeConge
    {
        return $this->createQueryBuilder('s')
            ->where('s.employe = :employe')
            ->andWhere('s.typeConge = :type')
            ->andWhere('s.annee = :annee')
            ->setParameter('employe', $employe)
            ->setParameter('type', $type)
            ->setParameter('annee', $annee)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return SoldeConge[]
     */
    public function findByEmployeAndAnnee(\App\Entity\Employe $employe, int $annee): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.employe = :employe')
            ->andWhere('s.annee = :annee')
            ->setParameter('employe', $employe)
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SoldeConge[]
     */
    public function findByEmploye(\App\Entity\Employe $employe): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('s.annee', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
