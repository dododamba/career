<?php

namespace App\Repository;

use App\Entity\TypeConges;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeConges>
 *
 * @method TypeConges|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeConges|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeConges[]    findAll()
 * @method TypeConge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeCongesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeConges::class);
    }

    /**
     * Récupère tous les types de congé actifs
     * 
     * @return TypeConges[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des types de congé par mot-clé
     * 
     * @param string $keyword
     * @return TypeConges[]
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.libelle LIKE :keyword')
            ->orWhere('t.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?TypeConges
    {
        return $this->createQueryBuilder('t')
            ->where('t.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
