<?php

namespace App\Repository;

use App\Entity\Institution;
use App\Entity\TypeActe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeActe>
 *
 * @method TypeActe|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeActe|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeActe[]    findAll()
 * @method TypeActe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeActeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeActe::class);
    }

    public function save(TypeActe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TypeActe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return TypeActe[]
     */
    public function findAllActive(?Institution $institution = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.actif = :actif')
            ->setParameter('actif', true);

        if ($institution !== null) {
            $qb->andWhere('t.institution = :institution OR t.institution IS NULL')
               ->setParameter('institution', $institution);
        }

        return $qb->orderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code, ?Institution $institution = null): ?TypeActe
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->setParameter('code', strtoupper($code));

        if ($institution !== null) {
            $qb->andWhere('t.institution = :institution OR t.institution IS NULL')
               ->setParameter('institution', $institution)
               ->orderBy('t.institution', 'DESC'); // institution-specific override first
        }

        $results = $qb->setMaxResults(1)->getQuery()->getResult();

        return $results[0] ?? null;
    }
}
