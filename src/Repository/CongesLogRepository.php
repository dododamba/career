<?php

namespace App\Repository;

use App\Entity\CongesLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CongesLog>
 *
 * @method CongesLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method CongesLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method CongesLog[]    findAll()
 * @method CongesLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CongesLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CongesLog::class);
    }

    /**
     * Récupère l'historique des logs pour un congé spécifique
     * 
     * @param int $congesId ID du congé
     * @return CongesLog[] Tableau des logs triés par date de création
     */
    public function findByConges(int $congesId): array
    {
        return $this->createQueryBuilder('cl')
            ->where('cl.conges = :congesId')
            ->setParameter('congesId', $congesId)
            ->orderBy('cl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CongesLog[]
     */
    public function findLatest(int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.conges', 'c')
            ->addSelect('c')
            ->leftJoin('c.employe', 'e')
            ->addSelect('e')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}