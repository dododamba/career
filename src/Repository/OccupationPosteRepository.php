<?php

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\OccupationPoste;
use App\Entity\Poste;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OccupationPoste>
 */
class OccupationPosteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OccupationPoste::class);
    }

    /**
     * Historique ordonné de toutes les occupations d'un poste
     */
    public function findHistoryByPoste(Poste $poste): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.employe', 'e')
            ->addSelect('e')
            ->where('o.poste = :poste')
            ->setParameter('poste', $poste)
            ->orderBy('o.date_debut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Historique des postes occupés par un agent
     */
    public function findHistoryByEmploye(Employe $employe): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.poste', 'p')
            ->leftJoin('p.structure', 'st')
            ->leftJoin('p.service', 's')
            ->addSelect('p', 'st', 's')
            ->where('o.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('o.date_debut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Occupation active courante pour un poste donné
     */
    public function findCurrentOccupation(Poste $poste): ?OccupationPoste
    {
        return $this->createQueryBuilder('o')
            ->where('o.poste = :poste')
            ->andWhere('o.is_actif = true')
            ->andWhere('o.date_fin IS NULL')
            ->setParameter('poste', $poste)
            ->orderBy('o.date_debut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
