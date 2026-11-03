<?php

namespace App\Repository;

use App\Entity\Interim;
use App\Entity\InterimLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InterimLog>
 *
 * @method InterimLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method InterimLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method InterimLog[]    findAll()
 * @method InterimLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterimLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterimLog::class);
    }

    /**
     * @return InterimLog[]
     */
    public function findByInterim(Interim $interim): array
    {
        return $this->createQueryBuilder('il')
            ->where('il.interim = :interim')
            ->setParameter('interim', $interim)
            ->orderBy('il.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
