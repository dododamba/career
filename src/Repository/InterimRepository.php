<?php

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Interim;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interim>
 *
 * @method Interim|null find($id, $lockMode = null, $lockVersion = null)
 * @method Interim|null findOneBy(array $criteria, array $orderBy = null)
 * @method Interim[]    findAll()
 * @method Interim[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interim::class);
    }

    /**
     * Recherche avec filtres optionnels pour une institution.
     *
     * @return Interim[]
     */
    public function findByFilters(
        Institution $institution,
        ?int $statut = null,
        ?Employe $agent = null,
        ?string $typeDelegation = null,
        ?string $search = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.titulaire', 't')
            ->leftJoin('i.interimaire', 'it')
            ->leftJoin('i.poste', 'p')
            ->leftJoin('i.structure', 's')
            ->where('i.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('i.dateDebut', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($statut !== null) {
            $qb->andWhere('i.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($agent !== null) {
            $qb->andWhere('(i.titulaire = :agent OR i.interimaire = :agent)')
                ->setParameter('agent', $agent);
        }

        if ($typeDelegation !== null && $typeDelegation !== '' && $typeDelegation !== 'all') {
            $qb->andWhere('i.typeDelegation = :typeDelegation')
                ->setParameter('typeDelegation', $typeDelegation);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('(i.reference LIKE :search OR i.motif LIKE :search OR t.nom LIKE :search OR t.prenom LIKE :search OR it.nom LIKE :search OR it.prenom LIKE :search OR p.titre LIKE :search OR s.nom LIKE :search)')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(
        Institution $institution,
        ?int $statut = null,
        ?Employe $agent = null,
        ?string $typeDelegation = null,
        ?string $search = null
    ): int {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(DISTINCT i.id)')
            ->leftJoin('i.titulaire', 't')
            ->leftJoin('i.interimaire', 'it')
            ->leftJoin('i.poste', 'p')
            ->leftJoin('i.structure', 's')
            ->where('i.institution = :institution')
            ->setParameter('institution', $institution);

        if ($statut !== null) {
            $qb->andWhere('i.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($agent !== null) {
            $qb->andWhere('(i.titulaire = :agent OR i.interimaire = :agent)')
                ->setParameter('agent', $agent);
        }

        if ($typeDelegation !== null && $typeDelegation !== '' && $typeDelegation !== 'all') {
            $qb->andWhere('i.typeDelegation = :typeDelegation')
                ->setParameter('typeDelegation', $typeDelegation);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('(i.reference LIKE :search OR i.motif LIKE :search OR t.nom LIKE :search OR t.prenom LIKE :search OR it.nom LIKE :search OR it.prenom LIKE :search OR p.titre LIKE :search OR s.nom LIKE :search)')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Recherche tous les intérims où l'employé est titulaire ou intérimaire.
     *
     * @return Interim[]
     */
    public function findByEmploye(Employe $employe): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.titulaire = :employe OR i.interimaire = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('i.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les intérims actifs couvrant une date pour un titulaire donné.
     *
     * @return Interim[]
     */
    public function findActiveForTitulaire(Employe $titulaire, ?\DateTimeInterface $date = null): array
    {
        $d = $date ?? new \DateTime();

        return $this->createQueryBuilder('i')
            ->where('i.titulaire = :titulaire')
            ->andWhere('i.statut = :statutActif')
            ->andWhere('i.dateDebut <= :date')
            ->andWhere('(i.dateFinEffective IS NULL AND i.dateFin >= :date) OR (i.dateFinEffective IS NOT NULL AND i.dateFinEffective >= :date)')
            ->setParameter('titulaire', $titulaire)
            ->setParameter('statutActif', Interim::STATUT_ACTIF)
            ->setParameter('date', $d->format('Y-m-d'))
            ->orderBy('i.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les délégations actives dont bénéficie un intérimaire à une date donnée.
     *
     * @return Interim[]
     */
    public function findActiveForInterimaire(Employe $interimaire, ?\DateTimeInterface $date = null): array
    {
        $d = $date ?? new \DateTime();

        return $this->createQueryBuilder('i')
            ->where('i.interimaire = :interimaire')
            ->andWhere('i.statut = :statutActif')
            ->andWhere('i.dateDebut <= :date')
            ->andWhere('(i.dateFinEffective IS NULL AND i.dateFin >= :date) OR (i.dateFinEffective IS NOT NULL AND i.dateFinEffective >= :date)')
            ->setParameter('interimaire', $interimaire)
            ->setParameter('statutActif', Interim::STATUT_ACTIF)
            ->setParameter('date', $d->format('Y-m-d'))
            ->orderBy('i.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie s'il existe déjà un intérim actif ou soumis pour le titulaire sur la même période.
     *
     * @return Interim[]
     */
    public function findOverlappingForTitulaire(
        Employe $titulaire,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->where('i.titulaire = :titulaire')
            ->andWhere('i.statut IN (:statuts)')
            ->andWhere('i.dateDebut <= :dateFin')
            ->andWhere('i.dateFin >= :dateDebut')
            ->setParameter('titulaire', $titulaire)
            ->setParameter('statuts', [Interim::STATUT_SOUMIS, Interim::STATUT_ACTIF])
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'));

        if ($excludeId !== null) {
            $qb->andWhere('i.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Vérifie si l'intérimaire est déjà engagé sur un autre intérim total sur la même période.
     *
     * @return Interim[]
     */
    public function findOverlappingForInterimaire(
        Employe $interimaire,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->where('i.interimaire = :interimaire')
            ->andWhere('i.typeDelegation = :typeTotal')
            ->andWhere('i.statut IN (:statuts)')
            ->andWhere('i.dateDebut <= :dateFin')
            ->andWhere('i.dateFin >= :dateDebut')
            ->setParameter('interimaire', $interimaire)
            ->setParameter('typeTotal', Interim::TYPE_INTERIM_TOTAL)
            ->setParameter('statuts', [Interim::STATUT_SOUMIS, Interim::STATUT_ACTIF])
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'));

        if ($excludeId !== null) {
            $qb->andWhere('i.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche les intérims actifs dont la date de fin est dépassée (pour expiration automatique).
     *
     * @return Interim[]
     */
    public function findExpiredActiveInterims(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.statut = :statutActif')
            ->andWhere('i.dateFin < :date')
            ->setParameter('statutActif', Interim::STATUT_ACTIF)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques globales des intérims pour une institution.
     */
    public function getStatistiques(Institution $institution): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i.statut, COUNT(i.id) as total')
            ->where('i.institution = :institution')
            ->groupBy('i.statut')
            ->setParameter('institution', $institution);

        $results = $qb->getQuery()->getArrayResult();

        $stats = [
            'total' => 0,
            'brouillons' => 0,
            'soumis' => 0,
            'actifs' => 0,
            'termines' => 0,
            'revoques' => 0,
            'refuses' => 0,
        ];

        foreach ($results as $row) {
            $count = (int) $row['total'];
            $stats['total'] += $count;
            match ((int) $row['statut']) {
                Interim::STATUT_BROUILLON => $stats['brouillons'] += $count,
                Interim::STATUT_SOUMIS => $stats['soumis'] += $count,
                Interim::STATUT_ACTIF => $stats['actifs'] += $count,
                Interim::STATUT_TERMINE => $stats['termines'] += $count,
                Interim::STATUT_REVOQUE => $stats['revoques'] += $count,
                Interim::STATUT_REFUSE => $stats['refuses'] += $count,
                default => null,
            };
        }

        return $stats;
    }
}
