<?php

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;
use DateTime;
use App\Entity\Institution; // Important: App\Entity\Institution, pas App\Repository\Institution

/**
 * @extends ServiceEntityRepository<Employe>
 */
class EmployeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employe::class);
    }


    
    /**
     * @param Service $service
     * @return int
     */
    public function findPresentEmployeesByService(Service $service): int
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0); // Start of day
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day'); // Start of next day
        
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->innerJoin('e.pointages', 'p')
            ->where('e.actif = :actif')
            ->andWhere('e.service = :service')
            ->andWhere('p.dateEntree >= :startDate')
            ->andWhere('p.dateEntree < :endDate')
            ->andWhere('p.dateSortie IS NOT NULL')
            ->setParameter('actif', true)
            ->setParameter('service', $service)
            ->setParameter('startDate', $today)
            ->setParameter('endDate', $tomorrow);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * @return array
     */
    public function findActiveWithStatus(): array
    {
        $today = new DateTime();
        $formattedDate = $today->format('Y-m-d');
        
        // Récupération des employés actifs
        $employesActifs = $this->findBy(['actif' => true]);
        
        // Récupération des employés présents aujourd'hui
        $qbPresent = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(p.employe) as id')
            ->from('App\Entity\Pointage', 'p')
            ->where('DATE(p.dateEntree) = :date')
            ->setParameter('date', $formattedDate);
            
        $presentEmployeeIds = array_map(
            function($item) { return $item['id']; },
            $qbPresent->getQuery()->getResult()
        );
        
        // Récupération des employés en retard
        $qbLate = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(p.employe) as id')
            ->from('App\Entity\Pointage', 'p')
            ->where('DATE(p.dateEntree) = :date')
            ->andWhere('TIME(p.dateEntree) > :lateTime')
            ->setParameter('date', $formattedDate)
            ->setParameter('lateTime', '09:00:00');
            
        $lateEmployeeIds = array_map(
            function($item) { return $item['id']; },
            $qbLate->getQuery()->getResult()
        );
        
        // Récupération des employés en congé
        $qbConge = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(c.employe) as id')
            ->from('App\Entity\Conges', 'c')
            ->where(':date BETWEEN c.dateDebut AND c.dateFin')
            ->setParameter('date', $formattedDate);
            
        $congeEmployeeIds = array_map(
            function($item) { return $item['id']; },
            $qbConge->getQuery()->getResult()
        );
        
        // Détermination du statut pour chaque employé
        foreach ($employesActifs as $employe) {
            $employeId = $employe->getId();
            
            if (in_array($employeId, $congeEmployeeIds)) {
                $employe->status = 'conge';
            } elseif (in_array($employeId, $lateEmployeeIds)) {
                $employe->status = 'late';
            } elseif (in_array($employeId, $presentEmployeeIds)) {
                $employe->status = 'present';
            } else {
                $employe->status = 'absent';
            }
        }
        
        return $employesActifs;
    }


    /**
     * Count employees present on a specific date who have both clocked in and out
     * 
     * @param DateTime $date
     * @return int
     */
    public function findPresentEmployeesToday(DateTime $date): int
    {
        // Create start and end of the day
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);
        
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->innerJoin('e.pointages', 'p')
            ->where('e.actif = :actif')
            ->andWhere('p.dateEntree >= :startOfDay')
            ->andWhere('p.dateEntree <= :endOfDay')
            ->andWhere('p.dateSortie IS NOT NULL')
            ->setParameter('actif', true)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Count employees who clocked in today but haven't clocked out yet
     * 
     * @param DateTime $date
     * @return int
     */
    public function findCurrentlyPresentEmployees(DateTime $date): int
    {
        // Create start and end of the day
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);
        
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->innerJoin('e.pointages', 'p')
            ->where('e.actif = :actif')
            ->andWhere('p.dateEntree >= :startOfDay')
            ->andWhere('p.dateEntree <= :endOfDay')
            ->andWhere('p.dateSortie IS NULL')
            ->setParameter('actif', true)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Count employees present on a specific date
     * 
     * @param DateTime $date
     * @return int
     */
    public function findPresentEmployeesByDate(DateTime $date): int
    {
        // Create start and end of the day
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);
        
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->innerJoin('e.pointages', 'p')
            ->where('e.actif = :actif')
            ->andWhere('p.dateEntree >= :startOfDay')
            ->andWhere('p.dateEntree <= :endOfDay')
            ->andWhere('p.dateSortie IS NOT NULL')
            ->setParameter('actif', true)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * Count employees by service
     * 
     * @param Service $service
     * @return int
     */
    public function countByService(Service $service): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.service = :service')
            ->andWhere('e.actif = :actif')
            ->setParameter('service', $service)
            ->setParameter('actif', true);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * Compte le nombre total d'employés d'une institution
     */
    public function countByInstitution(Institution $institution): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->innerJoin('e.user', 'u')
            ->where('u.institution = :institution')
            ->andWhere('e.actif = :actif')
            ->setParameter('institution', $institution)
            ->setParameter('actif', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les employés actifs d'une institution
     */
    public function countActiveByInstitution(Institution $institution): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->innerJoin('e.user', 'u')
            ->where('u.institution = :institution')
            ->andWhere('e.actif = true')
            ->andWhere('e.statut = true')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les employés d'une institution avec leurs relations
     */
    public function findByInstitutionWithRelations(Institution $institution)
    {
        return $this->createQueryBuilder('e')
            ->select('e', 'u', 'f', 's')
            ->innerJoin('e.user', 'u')
            ->leftJoin('e.fonction', 'f')
            ->leftJoin('e.service', 's')
            ->where('u.institution = :institution')
            ->andWhere('e.actif = true')
            ->setParameter('institution', $institution)
            ->orderBy('e.nom', 'ASC')
            ->addOrderBy('e.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

     /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Employe $entity, bool $flush = true): void
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
    public function remove(Employe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Trouve tous les employés actifs
     * @return Employe[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('e.nom', 'ASC')
            ->addOrderBy('e.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un employé par son matricule
     * @param string $matricule
     * @return Employe|null
     */
    public function findByMatricule(string $matricule): ?Employe
    {
        return $this->createQueryBuilder('e')
            ->where('e.matricule = :matricule')
            ->setParameter('matricule', $matricule)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les employés d'un service
     * @param int $serviceId
     * @return Employe[]
     */
    public function findByService(int $serviceId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.service = :serviceId')
            ->andWhere('e.actif = :actif')
            ->setParameter('serviceId', $serviceId)
            ->setParameter('actif', true)
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

 

    /**
     * Recherche d'employés par nom ou prénom
     * @param string $search
     * @param Institution|null $institution
     * @return Employe[]
     */
    public function searchByName(string $search, ?\App\Entity\Institution $institution = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.nom LIKE :search OR e.prenom LIKE :search')
            ->andWhere('e.actif = :actif')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('actif', true);

        if ($institution) {
            $qb->innerJoin('e.user', 'u')
               ->andWhere('u.institution = :institution')
               ->setParameter('institution', $institution);
        }

        return $qb->orderBy('e.nom', 'ASC')
                  ->addOrderBy('e.prenom', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les employés sans pointage aujourd'hui
     * @param Institution $institution
     * @return Employe[]
     */
    public function findWithoutPointageToday(\App\Entity\Institution $institution): array
    {
        $today = new \DateTime('today');
        
        return $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u')
            ->leftJoin('e.pointages', 'p', 'WITH', 'p.date = :today')
            ->where('u.institution = :institution')
            ->andWhere('e.actif = true')
            ->andWhere('p.id IS NULL')
            ->setParameter('institution', $institution)
            ->setParameter('today', $today)
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des employés par service pour une institution
     * @param Institution $institution
     * @return array
     */
    public function getStatsByServiceForInstitution(\App\Entity\Institution $institution): array
    {
        return $this->createQueryBuilder('e')
            ->select('s.nom as service', 'COUNT(e.id) as total')
            ->innerJoin('e.service', 's')
            ->where('e.institution = :institution')
            ->andWhere('e.actif = true')
            ->setParameter('institution', $institution)
            ->groupBy('s.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }


/**
 * Trouve les employés par institution avec pagination
 */
public function findByInstitution(Institution $institution, int $limit = null, int $offset = null): array
{
    $qb = $this->createQueryBuilder('e')
        ->innerJoin('e.user', 'u')
        ->leftJoin('e.service', 's')
        ->where('u.institution = :institution')
        ->setParameter('institution', $institution)
        ->orderBy('e.nom', 'ASC');

    if ($limit !== null) {
        $qb->setMaxResults($limit);
    }

    if ($offset !== null) {
        $qb->setFirstResult($offset);
    }

    return $qb->getQuery()->getResult();
}






}