<?php

namespace App\Repository;

use App\Entity\Institution;
use App\Entity\TypeAbsence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeAbsence>
 *
 * @method TypeAbsence|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeAbsence|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeAbsence[]    findAll()
 * @method TypeAbsence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeAbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeAbsence::class);
    }

    /**
     * @return TypeAbsence[]
     */
    public function findAllActive(?Institution $institution = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.actif = :actif')
            ->setParameter('actif', true);

        if ($institution !== null) {
            $qb->andWhere('t.institution = :institution OR t.institution IS NULL')
                ->setParameter('institution', $institution);
        }

        return $qb->orderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code, ?Institution $institution = null): ?TypeAbsence
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.code = :code')
            ->setParameter('code', strtoupper($code));

        if ($institution !== null) {
            $qb->andWhere('t.institution = :institution OR t.institution IS NULL')
                ->setParameter('institution', $institution)
                ->orderBy('t.institution', 'DESC'); // institution-specific takes precedence over NULL
        }

        return $qb->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
