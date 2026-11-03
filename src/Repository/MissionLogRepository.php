<?php

namespace App\Repository;

use App\Entity\Mission;
use App\Entity\MissionLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MissionLog>
 *
 * @method MissionLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method MissionLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method MissionLog[]    findAll()
 * @method MissionLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MissionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MissionLog::class);
    }

    /**
     * @return MissionLog[]
     */
    public function findByMission(Mission $mission): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.mission = :mission')
            ->setParameter('mission', $mission)
            ->orderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
