<?php

namespace App\Repository;

use App\Entity\Visite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Institution;

/**
 * @extends ServiceEntityRepository<Visite>
 */
class VisiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visite::class);
    }

    /**
     * Trouve les visites par date et institution
     */
    public function findByDateAndInstitution(\DateTimeInterface $date, $institution): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->leftJoin('e.service', 's')
            ->where('DATE(v.arrivee) = DATE(:date)')
            ->andWhere('u.institution = :institution')
            ->setParameter('date', $date)
            ->setParameter('institution', $institution)
            ->orderBy('v.arrivee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les visites par institution avec pagination
     */
    public function findByInstitution($institution, int $limit = null, int $offset = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->where('u.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('v.arrivee', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les visites par institution
     */
    public function countByInstitution($institution): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->where('u.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les visites actives pour une date et institution
     */
    public function countVisitesActivesParDateEtInstitution(\DateTimeInterface $date, $institution): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->where('DATE(v.arrivee) = DATE(:date)')
            ->andWhere('v.statut = false')
            ->andWhere('u.institution = :institution')
            ->setParameter('date', $date)
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les visites terminées pour une date et institution
     */
    public function countVisitesTermineesParDateEtInstitution(\DateTimeInterface $date, $institution): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->where('DATE(v.arrivee) = DATE(:date)')
            ->andWhere('v.statut = true')
            ->andWhere('u.institution = :institution')
            ->setParameter('date', $date)
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les visites pour cette semaine par institution
     */
    public function countVisitesSemaineEtInstitution($institution): int
    {
        $debut = new \DateTime('monday this week');
        $fin = new \DateTime('sunday this week');
        $fin->setTime(23, 59, 59);

        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->where('v.arrivee >= :debut')
            ->andWhere('v.arrivee <= :fin')
            ->andWhere('u.institution = :institution')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les visites pour ce mois par institution
     */
    public function countVisitesMoisEtInstitution($institution): int
    {
        $debut = new \DateTime('first day of this month');
        $fin = new \DateTime('last day of this month');
        $fin->setTime(23, 59, 59);

        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->where('v.arrivee >= :debut')
            ->andWhere('v.arrivee <= :fin')
            ->andWhere('u.institution = :institution')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les statistiques mensuelles par institution
     */
    public function getStatsMensuellesParInstitution(string $annee, $institution): array
    {
        return $this->createQueryBuilder('v')
            ->select('MONTH(v.arrivee) as mois, COUNT(v.id) as total')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->where('YEAR(v.arrivee) = :annee')
            ->andWhere('u.institution = :institution')
            ->groupBy('mois')
            ->setParameter('annee', $annee)
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les visites actives aujourd'hui par institution
     */
    public function findVisitesActivesAujourdhui($institution): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('v')
            ->join('v.employe', 'e')
            ->join('e.user', 'u')
            ->where('v.arrivee >= :today')
            ->andWhere('v.arrivee < :tomorrow')
            ->andWhere('v.statut = false')
            ->andWhere('u.institution = :institution')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('institution', $institution)
            ->orderBy('v.arrivee', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les visites d'un employé
     */
    public function findByEmploye($employe, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->where('v.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('v.arrivee', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

        /**
     * Compte les visites du jour pour une institution
     */
    public function countTodayByInstitution(Institution $institution, \DateTime $date): int
    {
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);
        
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.employe', 'e')
            ->innerJoin('e.user', 'u')
            ->where('u.institution = :institution')
            ->andWhere('v.arrivee BETWEEN :start AND :end')
            ->setParameter('institution', $institution)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les visites en attente pour une institution
     */
    public function countPendingByInstitution(Institution $institution, \DateTime $date): int
    {
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);
        
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.employe', 'e')
            ->innerJoin('e.user', 'u')
            ->where('u.institution = :institution')
            ->andWhere('v.arrivee BETWEEN :start AND :end')
            ->andWhere('v.statut = false')
            ->setParameter('institution', $institution)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->getQuery()
            ->getSingleScalarResult();
    }

}