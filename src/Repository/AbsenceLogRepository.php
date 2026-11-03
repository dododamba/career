<?php

namespace App\Repository;

use App\Entity\Absence;
use App\Entity\AbsenceLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbsenceLog>
 *
 * @method AbsenceLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbsenceLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbsenceLog[]    findAll()
 * @method AbsenceLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbsenceLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbsenceLog::class);
    }

    /**
     * @return AbsenceLog[]
     */
    public function findByAbsence(Absence $absence): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.absence = :absence')
            ->setParameter('absence', $absence)
            ->orderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
