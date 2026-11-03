<?php

namespace App\Repository;

use App\Entity\Pointage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Institution;
use App\Entity\Service;
use App\Entity\Employe;


/**
 * @extends ServiceEntityRepository<Pointage>
 */
class PointageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pointage::class);
    }




     /**
     * Count pointages by status and month
     *
     * @param string $statut The status to filter by (Présent, Absent, Retard, Congé)
     * @param string $month The month (1-12)
     * @param string $year The year (e.g. 2025)
     * @return int Number of pointages with the given status in the specific month
     */
    public function countPointagesParStatutEtMois(string $status, string $month, string $year): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->andWhere('MONTH(p.date) = :month')
            ->andWhere('YEAR(p.date) = :year')
            ->setParameter('status', $status)
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get monthly statistics for the given year
     *
     * @param string $year The year to get statistics for
     * @return array Monthly statistics with counts by status
     */
    public function getStatsMensuelles(string $year): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('MONTH(p.date) as mois, p.status, COUNT(p.id) as total')
            ->where('YEAR(p.date) = :year')
            ->groupBy('mois, p.status')
            ->orderBy('mois', 'ASC')
            ->setParameter('year', $year)
            ->getQuery()
            ->getResult();
            
        // Organize results by month
        $stats = [];
        foreach ($results as $row) {
            $month = $row['mois'];
            $status = $row['status'];
            $count = $row['total'];
            
            if (!isset($stats[$month])) {
                $stats[$month] = [
                    'Présent' => 0,
                    'Absent' => 0,
                    'Retard' => 0,
                    'Congé' => 0
                ];
            }
            
            $stats[$month][$status] = $count;
        }
        
        return $stats;
    }


     /**
     * Find top employees with best presence statistics
     *
     * @param int $limit Number of employees to return
     * @return array Top employees with their presence statistics
     */
    public function findTopPresenceEmployes(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->select('e.id, e.nom, e.prenom, COUNT(p.id) as totalPresence')
            ->join('p.employe', 'e')
            ->where('p.status = :status')
            ->groupBy('e.id')
            ->orderBy('totalPresence', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('status', 'Présent')
            ->getQuery()
            ->getResult();
    }
    


    /**
     * Compte le nombre de jours ouvrables dans un mois
     */
    private function countWorkingDays(int $year, int $month): int
    {
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');
        
        $days = 0;
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            // 6 = Saturday, 7 = Sunday
            $dayOfWeek = $currentDate->format('N');
            if ($dayOfWeek < 6) {
                $days++;
            }
            $currentDate->modify('+1 day');
        }
        
        return $days;
    }

    /**
 * Compte les pointages par statut, date et institution
 */
public function countPointagesParStatutEtDateEtInstitution(string $statut, \DateTimeInterface $date, $institution): int
{
    return $this->createQueryBuilder('p')
        ->select('COUNT(p.id)')
        ->join('p.employe', 'e')
        ->join('e.user', 'u')
        ->where('p.status = :statut')
        ->andWhere('DATE(p.date) = DATE(:date)')
        ->andWhere('u.institution = :institution')
        ->setParameter('statut', $statut)
        ->setParameter('date', $date)
        ->setParameter('institution', $institution)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Trouve les pointages par date et institution
 */
public function findByDateAndInstitution(\DateTimeInterface $date, $institution): array
{
    return $this->createQueryBuilder('p')
        ->join('p.employe', 'e')
        ->join('e.user', 'u')
        ->leftJoin('e.service', 's') // Left join pour éviter les erreurs si pas de service
        ->where('DATE(p.date) = DATE(:date)')
        ->andWhere('u.institution = :institution')
        ->setParameter('date', $date)
        ->setParameter('institution', $institution)
        ->orderBy('p.heure_arrivee', 'ASC') // Utiliser le nom de propriété correct
        ->getQuery()
        ->getResult();
}

/**
 * Compte les pointages par statut, mois et institution
 */
public function countPointagesParStatutEtMoisEtInstitution(string $statut, string $mois, string $annee, $institution): int
{
    return $this->createQueryBuilder('p')
        ->select('COUNT(p.id)')
        ->join('p.employe', 'e')
        ->join('e.user', 'u')
        ->where('p.status = :statut')
        ->andWhere('MONTH(p.date) = :mois')
        ->andWhere('YEAR(p.date) = :annee')
        ->andWhere('u.institution = :institution')
        ->setParameter('statut', $statut)
        ->setParameter('mois', $mois)
        ->setParameter('annee', $annee)
        ->setParameter('institution', $institution)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Obtient les statistiques mensuelles par institution
 */
public function getStatsMensuellesParInstitution(string $annee, $institution): array
{
    return $this->createQueryBuilder('p')
        ->select('MONTH(p.date) as mois, p.status as statut, COUNT(p.id) as total')
        ->join('p.employe', 'e')
        ->join('e.user', 'u')
        ->where('YEAR(p.date) = :annee')
        ->andWhere('u.institution = :institution')
        ->groupBy('mois, statut')
        ->setParameter('annee', $annee)
        ->setParameter('institution', $institution)
        ->getQuery()
        ->getResult();
}

/**
 * Trouve les employés avec les meilleures présences par institution
 */
public function findTopPresenceEmployesByInstitution(int $limit, $institution): array
{
    $rows = $this->createQueryBuilder('p')
        ->select('e.id, e.nom, e.prenom, s.nom as service, SUM(CASE WHEN p.est_present = true OR p.status = :statut THEN 1 ELSE 0 END) as total_presences, AVG(CASE WHEN p.est_present = true OR p.status = :statut THEN 100.0 ELSE 0.0 END) as taux_presence')
        ->join('p.employe', 'e')
        ->leftJoin('e.service', 's')
        ->join('e.user', 'u')
        ->where('u.institution = :institution')
        ->groupBy('e.id, e.nom, e.prenom, s.nom')
        ->orderBy('total_presences', 'DESC')
        ->setMaxResults($limit)
        ->setParameter('statut', 'Présent')
        ->setParameter('institution', $institution)
        ->getQuery()
        ->getResult();

    return array_map(function ($row) {
        $row['service'] = $row['service'] ?? 'Général';
        $row['total_presences'] = (int) ($row['total_presences'] ?? 0);
        $row['taux_presence'] = round((float) ($row['taux_presence'] ?? 0));
        return $row;
    }, $rows);
}

/**
 * Trouve les pointages par date (méthode originale sans filtre d'institution)
 */
public function findByDate(\DateTimeInterface $date): array
{
    return $this->createQueryBuilder('p')
        ->leftJoin('p.employe', 'e')
        ->leftJoin('e.service', 's')
        ->where('DATE(p.date) = DATE(:date)')
        ->setParameter('date', $date)
        ->orderBy('p.heure_arrivee', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Compte les pointages par statut et date (méthode originale sans filtre d'institution)
 */
public function countPointagesParStatutEtDate(string $statut, \DateTimeInterface $date): int
{
    return $this->createQueryBuilder('p')
        ->select('COUNT(p.id)')
        ->where('p.status = :statut')
        ->andWhere('DATE(p.date) = DATE(:date)')
        ->setParameter('statut', $statut)
        ->setParameter('date', $date)
        ->getQuery()
        ->getSingleScalarResult();
}


/**
     * Compte les présents aujourd'hui pour une institution
     */
    public function countPresentsTodayByInstitution(Institution $institution, \DateTime $date): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.employe)')
            ->innerJoin('p.employe', 'e')
            ->innerJoin('e.user', 'u')
            ->where('u.institution = :institution')
            ->andWhere('p.date = :date')
            ->andWhere('p.est_present = true')
            ->setParameter('institution', $institution)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }



 /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Pointage $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Pointage $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Compte les présents aujourd'hui (sans filtrage par institution)
     */
    public function countPresentsToday(): int
    {
        try {
            $today = new \DateTime('today');
            
            return $this->createQueryBuilder('p')
                ->select('COUNT(DISTINCT p.employe)')
                ->where('p.date = :date')
                ->andWhere('p.est_present = true')
                ->setParameter('date', $today)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Compte les absents aujourd'hui (sans filtrage par institution)
     */
    public function countAbsentsToday(): int
    {
        try {
            $today = new \DateTime('today');
            
            return $this->createQueryBuilder('p')
                ->select('COUNT(DISTINCT p.employe)')
                ->where('p.date = :date')
                ->andWhere('p.est_present = false')
                ->setParameter('date', $today)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

  

    /**
     * Taux de présence par mois pour une institution
     * @param \App\Entity\Institution $institution
     * @param int $months
     * @return array
     */
    public function getPresenceRateByMonthAndInstitution(\App\Entity\Institution $institution, int $months = 6): array
    {
        try {
            $startDate = new \DateTime("-{$months} months");
            $startDate->modify('first day of this month');
            
            $qb = $this->createQueryBuilder('p')
                ->select('p.date', 'p.est_present')
                ->innerJoin('p.employe', 'e')
                ->where('e.institution = :institution')
                ->andWhere('p.date >= :startDate')
                ->setParameter('institution', $institution)
                ->setParameter('startDate', $startDate)
                ->orderBy('p.date', 'ASC');

            $pointages = $qb->getQuery()->getResult();

            $monthlyData = [];
            foreach ($pointages as $p) {
                $monthKey = $p['date']->format('Y-m');
                $monthLabel = $p['date']->format('M Y');
                if (!isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = [
                        'month' => $monthLabel,
                        'total' => 0,
                        'presents' => 0,
                    ];
                }
                $monthlyData[$monthKey]['total']++;
                if ($p['est_present']) {
                    $monthlyData[$monthKey]['presents']++;
                }
            }

            $formattedResults = [];
            foreach ($monthlyData as $data) {
                $rate = $data['total'] > 0 ? ($data['presents'] / $data['total']) * 100 : 0;
                $formattedResults[] = [
                    'month' => $data['month'],
                    'rate' => round($rate, 1)
                ];
            }

            return $formattedResults;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Top des services avec le plus d'absences pour une institution
     * @param \App\Entity\Institution $institution
     * @param int $days
     * @return array
     */
    public function getTopAbsencesByServiceAndInstitution(\App\Entity\Institution $institution, int $days = 30): array
    {
        try {
            $startDate = new \DateTime("-{$days} days");
            
            return $this->createQueryBuilder('p')
                ->select('s.nom as service', 'COUNT(p.id) as absences')
                ->innerJoin('p.employe', 'e')
                ->innerJoin('e.service', 's')
                ->where('e.institution = :institution')
                ->andWhere('p.date >= :startDate')
                ->andWhere('p.est_present = false')
                ->setParameter('institution', $institution)
                ->setParameter('startDate', $startDate)
                ->groupBy('s.id')
                ->orderBy('absences', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Taux de présence moyen sur N jours pour une institution
     * @param \App\Entity\Institution $institution
     * @param int $days
     * @return float
     */
    public function getAveragePresenceRateByInstitution(\App\Entity\Institution $institution, int $days = 30): float
    {
        try {
            $startDate = new \DateTime("-{$days} days");
            
            $result = $this->createQueryBuilder('p')
                ->select(
                    'COUNT(p.id) as total',
                    'SUM(CASE WHEN p.est_present = true THEN 1 ELSE 0 END) as presents'
                )
                ->innerJoin('p.employe', 'e')
                ->innerJoin('e.user', 'u')
                ->where('u.institution = :institution')
                ->andWhere('p.date >= :startDate')
                ->setParameter('institution', $institution)
                ->setParameter('startDate', $startDate)
                ->getQuery()
                ->getSingleResult();

            return $result['total'] > 0 
                ? ($result['presents'] / $result['total']) * 100 
                : 0;
        } catch (\Exception $e) {
            return 75.5; // Valeur par défaut
        }
    }

    /**
     * Récupère les derniers pointages d'une institution
     * @param \App\Entity\Institution $institution
     * @param int $limit
     * @return array
     */
    public function findLatestByInstitution(\App\Entity\Institution $institution, int $limit = 10): array
    {
        try {
            return $this->createQueryBuilder('p')
                ->select('p', 'e', 's')
                ->innerJoin('p.employe', 'e')
                ->innerJoin('e.user', 'u')
                ->leftJoin('e.service', 's')
                ->where('u.institution = :institution')
                ->andWhere('p.date = :today')
                ->setParameter('institution', $institution)
                ->setParameter('today', (new \DateTime())->format('Y-m-d'))
                ->orderBy('p.heure_arrivee', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Trouve le pointage d'un employé pour une date donnée
     * @param int $employeId
     * @param \DateTime $date
     * @return Pointage|null
     */
    public function findByEmployeAndDate(int $employeId, \DateTime $date): ?Pointage
    {
        return $this->createQueryBuilder('p')
            ->where('p.employe = :employeId')
            ->andWhere('p.date = :date')
            ->setParameter('employeId', $employeId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère l'historique de pointage d'un employé
     * @param int $employeId
     * @param int $days
     * @return array
     */
    public function getEmployeHistory(int $employeId, int $days = 30): array
    {
        $startDate = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('p')
            ->where('p.employe = :employeId')
            ->andWhere('p.date >= :startDate')
            ->setParameter('employeId', $employeId)
            ->setParameter('startDate', $startDate)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par service pour une institution
     * @param \App\Entity\Institution $institution
     * @return array
     */
    public function getStatsByServiceAndInstitution(\App\Entity\Institution $institution): array
    {
        try {
            return $this->createQueryBuilder('p')
                ->select(
                    's.nom as service',
                    'COUNT(DISTINCT e.id) as total_employes',
                    'AVG(CASE WHEN p.est_present = true THEN 100 ELSE 0 END) as taux_presence'
                )
                ->innerJoin('p.employe', 'e')
                ->innerJoin('e.service', 's')
                ->where('e.institution = :institution')
                ->andWhere('p.date >= :startDate')
                ->setParameter('institution', $institution)
                ->setParameter('startDate', (new \DateTime('-30 days')))
                ->groupBy('s.id')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Taux de présence global pour une institution
     * @param \App\Entity\Institution $institution
     * @return float
     */
    public function getGlobalPresenceRateByInstitution(\App\Entity\Institution $institution): float
    {
        try {
            $today = new \DateTime('today');
            
            $result = $this->createQueryBuilder('p')
                ->select('COUNT(DISTINCT p.employe) as presents')
                ->innerJoin('p.employe', 'e')
                ->innerJoin('e.user', 'u')
                ->where('u.institution = :institution')
                ->andWhere('p.date = :today')
                ->andWhere('p.est_present = true')
                ->setParameter('institution', $institution)
                ->setParameter('today', $today->format('Y-m-d'))
                ->getQuery()
                ->getSingleResult();

            // Récupérer le total des employés de l'institution
            $totalEmployes = $this->getEntityManager()
                ->getRepository(\App\Entity\Employe::class)
                ->countActiveByInstitution($institution);

            return $totalEmployes > 0 
                ? ($result['presents'] / $totalEmployes) * 100 
                : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Statistiques annuelles pour une institution
     * @param \App\Entity\Institution $institution
     * @return array
     */
    public function getYearlyStatsByInstitution(\App\Entity\Institution $institution): array
    {
        try {
            $startDate = new \DateTime('-12 months');
            
            return $this->createQueryBuilder('p')
                ->select(
                    "DATE_FORMAT(p.date, '%Y-%m') as month",
                    'COUNT(p.id) as total_pointages',
                    'AVG(CASE WHEN p.est_present = true THEN 100 ELSE 0 END) as taux_presence'
                )
                ->innerJoin('p.employe', 'e')
                ->innerJoin('e.user', 'u')
                ->where('u.institution = :institution')
                ->andWhere('p.date >= :startDate')
                ->setParameter('institution', $institution)
                ->setParameter('startDate', $startDate)
                ->groupBy('month')
                ->orderBy('month', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les données pour le graphique de présence mensuelle (sans institution)
     * @return array
     */
    public function getPresenceRateByMonth(): array
    {
        try {
            $months = 6;
            $startDate = new \DateTime("-{$months} months");
            $startDate->modify('first day of this month');
            
            $qb = $this->createQueryBuilder('p')
                ->select('p.date', 'p.est_present')
                ->where('p.date >= :startDate')
                ->setParameter('startDate', $startDate)
                ->orderBy('p.date', 'ASC');

            $pointages = $qb->getQuery()->getResult();

            $monthlyData = [];
            foreach ($pointages as $p) {
                $monthKey = $p['date']->format('Y-m');
                $monthLabel = $p['date']->format('M Y');
                if (!isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = [
                        'month' => $monthLabel,
                        'total' => 0,
                        'presents' => 0,
                    ];
                }
                $monthlyData[$monthKey]['total']++;
                if ($p['est_present']) {
                    $monthlyData[$monthKey]['presents']++;
                }
            }

            $formattedResults = [];
            foreach ($monthlyData as $data) {
                $rate = $data['total'] > 0 ? ($data['presents'] / $data['total']) * 100 : 0;
                $formattedResults[] = [
                    'month' => $data['month'],
                    'rate' => round($rate, 1)
                ];
            }

            return $formattedResults;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les services avec le plus d'absences (sans institution)
     * @return array
     */
    public function getTopAbsencesByService(): array
    {
        try {
            $startDate = new \DateTime('-30 days');
            
            return $this->createQueryBuilder('p')
                ->select('s.nom as service', 'COUNT(p.id) as absences')
                ->innerJoin('p.employe', 'e')
                ->innerJoin('e.service', 's')
                ->where('p.date >= :startDate')
                ->andWhere('p.est_present = false')
                ->setParameter('startDate', $startDate)
                ->groupBy('s.id')
                ->orderBy('absences', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }



/**
 * Compte les présents par service et date
 */
public function countPresentsByServiceAndDate(Service $service, \DateTime $date): int
{
    return $this->createQueryBuilder('p')
        ->select('COUNT(DISTINCT p.employe)')
        ->innerJoin('p.employe', 'e')
        ->where('e.service = :service')
        ->andWhere('p.date = :date')
        ->andWhere('p.est_present = true')
        ->setParameter('service', $service)
        ->setParameter('date', $date->format('Y-m-d'))
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Trouve la dernière présence d'un employé
 */
public function findLastPresenceByEmploye(Employe $employe): ?Pointage
{
    return $this->createQueryBuilder('p')
        ->where('p.employe = :employe')
        ->andWhere('p.est_present = true')
        ->setParameter('employe', $employe)
        ->orderBy('p.date', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}

// ========================================
// Ajouter ces méthodes dans CongesRepository.php (en plus de celles existantes)
// ========================================

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