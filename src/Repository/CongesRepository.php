<?php

namespace App\Repository;

use App\Entity\Conges;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Institution;

use App\Entity\Service;
use App\Entity\Employe;

use DateTime;
/**
 * @extends ServiceEntityRepository<Conges>
 */
class CongesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conges::class);
    }

    /**
     * Compte le nombre de congés actuellement actifs
     */
    public function countActiveLeaves(): int
    {
        $today = new \DateTime();
        
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.dateDebut <= :today')
            ->andWhere('c.dateFin >= :today')
            ->andWhere('c.statut = :statut')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('statut', 'Approuvé');
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Compte les congés par statut
     */
    public function countByStatus(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.statut, COUNT(c.id) as count')
            ->groupBy('c.statut');
        
        $results = $qb->getQuery()->getResult();
        
        $formatted = [];
        foreach ($results as $row) {
            $formatted[] = [
                'label' => $row['statut'],
                'value' => $row['count'],
            ];
        }
        
        return $formatted;
    }

    /**
     * Récupère la distribution des congés par mois
     */
    public function getLeaveDistributionByMonth(): array
    {
        $result = [];
        $currentYear = (new \DateTime())->format('Y');
        
        for ($month = 1; $month <= 12; $month++) {
            $qb = $this->createQueryBuilder('c')
                ->select('COUNT(c.id) as count')
                ->where('MONTH(c.dateDebut) = :month OR MONTH(c.dateFin) = :month')
                ->andWhere('YEAR(c.dateDebut) = :year OR YEAR(c.dateFin) = :year')
                ->andWhere('c.statut = :statut')
                ->setParameter('month', $month)
                ->setParameter('year', $currentYear)
                ->setParameter('statut', 'Approuvé');
            
            $count = (int) $qb->getQuery()->getSingleScalarResult();
            
            $monthName = (new \DateTime("$currentYear-$month-01"))->format('M');
            $result[] = [
                'month' => $monthName,
                'count' => $count
            ];
        }
        
        return $result;
    }

  
    /**
     * Applique les filtres à la requête
     * 
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $filters
     */
    private function applyFilters($qb, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if ($field === 'employe') {
                    $qb->andWhere('c.employe = :employe')
                       ->setParameter('employe', $value);
                } elseif ($field === 'statut') {
                    $qb->andWhere('c.statut = :statut')
                       ->setParameter('statut', $value);
                } elseif ($field === 'type_conge') {
                    $qb->andWhere('c.typeConge = :typeConge')
                       ->setParameter('typeConge', $value);
                } elseif ($field === 'dateDebut') {
                    $qb->andWhere('c.dateDebut >= :dateDebut')
                       ->setParameter('dateDebut', $value);
                } elseif ($field === 'dateFin') {
                    $qb->andWhere('c.dateFin <= :dateFin')
                       ->setParameter('dateFin', $value);
                }
            }
        }
    }
    
    /**
     * Récupère les statistiques mensuelles des congés pour une année donnée
     * 
     * @param int $year L'année pour laquelle récupérer les statistiques
     * @return array Statistiques mensuelles
     */
    public function getMonthlyStats(int $year = null): array
    {
        if ($year === null) {
            $year = date('Y');
        }
        
        $startDate = new \DateTime($year . '-01-01');
        $endDate = new \DateTime($year . '-12-31');
        
        $qb = $this->createQueryBuilder('c')
            ->select('MONTH(c.dateDebut) as month, COUNT(c.id) as count, c.statut')
            ->where('c.dateDebut BETWEEN :start AND :end')
            ->groupBy('month, c.statut')
            ->orderBy('month', 'ASC')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);
        
        $results = $qb->getQuery()->getResult();
        
        // Initialiser le tableau avec tous les mois
        $stats = [];
        for ($i = 1; $i <= 12; $i++) {
            $stats[$i] = [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];
        }
        
        // Remplir avec les résultats
        foreach ($results as $result) {
            $month = (int)$result['month'];
            $count = (int)$result['count'];
            $status = (int)$result['statut'];
            
            if ($status === 0) {
                $stats[$month]['pending'] = $count;
            } elseif ($status === 1) {
                $stats[$month]['approved'] = $count;
            } elseif ($status === 2) {
                $stats[$month]['rejected'] = $count;
            }
            
            $stats[$month]['total'] += $count;
        }
        
        return $stats;
    }

     /**
     * @param DateTime $date
     * @return int
     */
    public function findEmployeesOnLeave(DateTime $date): int
    {
        $formattedDate = $date->format('Y-m-d');
        
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.employe)')
            ->where(':date BETWEEN c.dateDebut AND c.dateFin')
            ->setParameter('date', $formattedDate);
            
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * @param Service $service
     * @param DateTime $date
     * @return int
     */
    public function findEmployeesOnLeaveByService(Service $service, DateTime $date): int
    {
        $formattedDate = $date->format('Y-m-d');
        
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.employe)')
            ->join('c.employe', 'e')
            ->where(':date BETWEEN c.dateDebut AND c.dateFin')
            ->andWhere('e.service = :service')
            ->setParameter('date', $formattedDate)
            ->setParameter('service', $service);
            
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
  
    /**
     * Find total leave days between two dates
     * 
     * @param DateTime $from
     * @param DateTime $to
     * @return int
     */
    public function findTotalLeaveDays(DateTime $from, DateTime $to): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('SUM((CASE 
                WHEN c.dateFin > :to THEN :to 
                ELSE c.dateFin 
                END) - (CASE 
                WHEN c.dateDebut < :from THEN :from 
                ELSE c.dateDebut
                END) + 1) as days')
            ->where('c.dateDebut <= :to')
            ->andWhere('c.dateFin >= :from');
        
        // Use individual setParameter calls
        $qb->setParameter('from', $from->format('Y-m-d'))
           ->setParameter('to', $to->format('Y-m-d'));

        $result = $qb->getQuery()->getSingleScalarResult();
        
        return (int) $result;
    }
    
    /**
     * Count leaves by type in a specific period
     * 
     * @param string $type
     * @param DateTime $from 
     * @param DateTime $to
     * @return int
     */
    public function findByTypeInPeriod($type, \DateTime $from, \DateTime $to)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.typeConge = :type') // <-- Change 'type' to the actual property name
            ->andWhere('c.dateDebut <= :to')
            ->andWhere('c.dateFin >= :from');
        
        $qb->setParameter('type', $type)
           ->setParameter('from', $from->format('Y-m-d'))
           ->setParameter('to', $to->format('Y-m-d'));
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * Find average leave days per employee
     * 
     * @param DateTime $from
     * @param DateTime $to
     * @return float
     */
    public function findAverageLeaveDays(DateTime $from, DateTime $to): float
    {
        $qb = $this->createQueryBuilder('c')
            ->select('AVG((CASE 
                WHEN c.dateFin > :to THEN :to 
                ELSE c.dateFin 
                END) - (CASE 
                WHEN c.dateDebut < :from THEN :from 
                ELSE c.dateDebut
                END) + 1) as avg_days')
            ->where('c.dateDebut <= :to')
            ->andWhere('c.dateFin >= :from');
        
        // Use individual setParameter calls
        $qb->setParameter('from', $from->format('Y-m-d'))
           ->setParameter('to', $to->format('Y-m-d'));
        
        $result = $qb->getQuery()->getSingleScalarResult();
        
        return (float) $result;
    }

    /**
 * Trouve les congés avec filtres et pagination
 */
public function findByFilters(array $filters, int $limit, int $offset): array
{
    $qb = $this->createQueryBuilder('c')
        ->leftJoin('c.employe', 'e')
        ->leftJoin('c.typeConge', 't')
        ->orderBy('c.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->setFirstResult($offset);

    // Appliquer les filtres
    if (!empty($filters['statut'])) {
        $qb->andWhere('c.statut = :statut')
           ->setParameter('statut', $filters['statut']);
    }

    if (!empty($filters['employe'])) {
        $qb->andWhere('c.employe = :employe')
           ->setParameter('employe', $filters['employe']);
    }

    if (!empty($filters['typeConge'])) {
        $qb->andWhere('c.typeConge = :typeConge')
           ->setParameter('typeConge', $filters['typeConge']);
    }

    // Filtre par date
    if (!empty($filters['dateDebut'])) {
        $qb->andWhere('c.dateDebut >= :dateDebut')
           ->setParameter('dateDebut', $filters['dateDebut']);
    }

    if (!empty($filters['dateFin'])) {
        $qb->andWhere('c.dateFin <= :dateFin')
           ->setParameter('dateFin', $filters['dateFin']);
    }

    return $qb->getQuery()->getResult();
}

/**
 * Compte les congés avec filtres
 */
public function countByFilters(array $filters): int
{
    $qb = $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->leftJoin('c.employe', 'e');

    // Appliquer les mêmes filtres que findByFilters
    if (!empty($filters['statut'])) {
        $qb->andWhere('c.statut = :statut')
           ->setParameter('statut', $filters['statut']);
    }

    if (!empty($filters['employe'])) {
        $qb->andWhere('c.employe = :employe')
           ->setParameter('employe', $filters['employe']);
    }

    if (!empty($filters['typeConge'])) {
        $qb->andWhere('c.typeConge = :typeConge')
           ->setParameter('typeConge', $filters['typeConge']);
    }

    if (!empty($filters['dateDebut'])) {
        $qb->andWhere('c.dateDebut >= :dateDebut')
           ->setParameter('dateDebut', $filters['dateDebut']);
    }

    if (!empty($filters['dateFin'])) {
        $qb->andWhere('c.dateFin <= :dateFin')
           ->setParameter('dateFin', $filters['dateFin']);
    }

    return $qb->getQuery()->getSingleScalarResult();
}

/**
 * Trouve les congés par institution
 */
public function findByInstitution($institution, int $limit = null, int $offset = null): array
{
    $qb = $this->createQueryBuilder('c')
        ->join('c.employe', 'e')
        ->join('e.user', 'u')
        ->where('u.institution = :institution')
        ->setParameter('institution', $institution)
        ->orderBy('c.createdAt', 'DESC');

    if ($limit) {
        $qb->setMaxResults($limit);
    }

    if ($offset) {
        $qb->setFirstResult($offset);
    }

    return $qb->getQuery()->getResult();
}

/**
 * Compte les congés par institution
 */
public function countByInstitution($institution): int
{
    return $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->join('c.employe', 'e')
        ->join('e.user', 'u')
        ->where('u.institution = :institution')
        ->setParameter('institution', $institution)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Trouve les congés par employé
 */
public function findByEmploye($employe, int $limit = null): array
{
    $qb = $this->createQueryBuilder('c')
        ->where('c.employe = :employe')
        ->setParameter('employe', $employe)
        ->orderBy('c.createdAt', 'DESC');

    if ($limit) {
        $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
}

/**
 * Trouve les congés en cours pour une date donnée
 */
public function findCongesEnCoursADate(\DateTimeInterface $date): array
{
    return $this->createQueryBuilder('c')
        ->where('c.dateDebut <= :date')
        ->andWhere('c.dateFin >= :date')
        ->andWhere('c.statut = 1') // Seulement les congés approuvés
        ->setParameter('date', $date)
        ->getQuery()
        ->getResult();
}

/**
 * Statistiques mensuelles des congés
 */
public function getStatsMensuelles(int $annee): array
{
    return $this->createQueryBuilder('c')
        ->select('MONTH(c.dateDebut) as mois, SUM(c.dateDiff) as totalJours, COUNT(c.id) as nombreConges')
        ->where('YEAR(c.dateDebut) = :annee')
        ->andWhere('c.statut = 1') // Seulement les congés approuvés
        ->groupBy('mois')
        ->setParameter('annee', $annee)
        ->getQuery()
        ->getResult();
}

/**
 * Statistiques par type de congé
 */
public function getStatsParType(): array
{
    return $this->createQueryBuilder('c')
        ->select('t.libelle as typeConge, COUNT(c.id) as nombre, SUM(c.dateDiff) as totalJours')
        ->join('c.typeConge', 't')
        ->where('c.statut = 1') // Seulement les congés approuvés
        ->groupBy('t.id')
        ->getQuery()
        ->getResult();
}


 /**
     * Compte les congés actifs pour une institution
     */
    public function countActiveLeavesbyInstitution(Institution $institution): int
    {
        $today = new \DateTime('today');
        
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->innerJoin('c.employe', 'e')
            ->innerJoin('e.user', 'u')
            ->where('u.institution = :institution')
            ->andWhere('c.dateDebut <= :today')
            ->andWhere('c.dateFin >= :today')
            ->andWhere('c.statut = 1') // Approuvé
            ->setParameter('institution', $institution)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les congés en attente pour une institution
     */
    public function countPendingByInstitution(Institution $institution): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->innerJoin('c.employe', 'e')
            ->innerJoin('e.user', 'u')
            ->where('u.institution = :institution')
            ->andWhere('c.statut = 0') // En attente
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Répartition des congés par statut pour une institution
     */
    public function countByStatusAndInstitution(Institution $institution): array
    {
        $results = $this->createQueryBuilder('c')
            ->select('c.statut', 'COUNT(c.id) as count')
            ->innerJoin('c.employe', 'e')
            ->innerJoin('e.user', 'u')
            ->where('u.institution = :institution')
            ->setParameter('institution', $institution)
            ->groupBy('c.statut')
            ->getQuery()
            ->getResult();

        // Formater les résultats
        $formatted = [];
        $statusLabels = [
            0 => 'En attente',
            1 => 'Approuvé',
            2 => 'Refusé',
            3 => 'Annulé'
        ];

        foreach ($results as $result) {
            $formatted[] = [
                'label' => $statusLabels[$result['statut']] ?? 'Inconnu',
                'value' => $result['count']
            ];
        }

        return $formatted;
    }


    /**
 * Trouve un congé actif pour un employé à une date donnée
 */
public function findActiveByEmployeAndDate(Employe $employe, \DateTime $date): ?Conges
{
    return $this->createQueryBuilder('c')
        ->where('c.employe = :employe')
        ->andWhere('c.dateDebut <= :date')
        ->andWhere('c.dateFin >= :date')
        ->andWhere('c.statut = 1') // Approuvé
        ->setParameter('employe', $employe)
        ->setParameter('date', $date->format('Y-m-d'))
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}

}