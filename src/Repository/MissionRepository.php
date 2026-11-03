<?php

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Mission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mission>
 *
 * @method Mission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Mission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Mission[]    findAll()
 * @method Mission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mission::class);
    }

    /**
     * Recherche avec filtres optionnels pour une institution.
     *
     * @return Mission[]
     */
    public function findByFilters(
        Institution $institution,
        ?int $statut = null,
        ?Employe $agent = null,
        ?string $search = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.chefMission', 'cm')
            ->leftJoin('m.participants', 'p')
            ->leftJoin('p.employe', 'pe')
            ->where('m.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('m.dateDebut', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($statut !== null) {
            $qb->andWhere('m.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($agent !== null) {
            $qb->andWhere('(m.chefMission = :agent OR pe = :agent)')
                ->setParameter('agent', $agent);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('(m.objet LIKE :search OR m.destination LIKE :search OR m.reference LIKE :search OR cm.nom LIKE :search OR pe.nom LIKE :search)')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(
        Institution $institution,
        ?int $statut = null,
        ?Employe $agent = null,
        ?string $search = null
    ): int {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.id)')
            ->leftJoin('m.chefMission', 'cm')
            ->leftJoin('m.participants', 'p')
            ->leftJoin('p.employe', 'pe')
            ->where('m.institution = :institution')
            ->setParameter('institution', $institution);

        if ($statut !== null) {
            $qb->andWhere('m.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($agent !== null) {
            $qb->andWhere('(m.chefMission = :agent OR pe = :agent)')
                ->setParameter('agent', $agent);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('(m.objet LIKE :search OR m.destination LIKE :search OR m.reference LIKE :search OR cm.nom LIKE :search OR pe.nom LIKE :search)')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Recherche les missions d'un agent spécifique.
     *
     * @return Mission[]
     */
    public function findByEmploye(Employe $employe): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.participants', 'p')
            ->where('m.chefMission = :employe OR p.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('m.dateDebut', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de chevauchement de mission pour un employé donné.
     *
     * @return Mission[]
     */
    public function findOverlappingForEmploye(
        Employe $employe,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeMissionId = null
    ): array {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.participants', 'p')
            ->where('(m.chefMission = :employe OR p.employe = :employe)')
            ->andWhere('m.statut IN (:statuts)')
            ->andWhere('m.dateDebut <= :dateFin')
            ->andWhere('m.dateFin >= :dateDebut')
            ->setParameter('employe', $employe)
            ->setParameter('statuts', [
                Mission::STATUT_SOUMISE,
                Mission::STATUT_VALIDEE,
                Mission::STATUT_EN_COURS,
            ])
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'));

        if ($excludeMissionId !== null) {
            $qb->andWhere('m.id != :excludeMissionId')
                ->setParameter('excludeMissionId', $excludeMissionId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques globales des missions pour une institution.
     */
    public function getStatistiques(Institution $institution, ?int $annee = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.institution = :institution')
            ->setParameter('institution', $institution);

        if ($annee !== null) {
            $qb->andWhere('m.dateDebut >= :debutAnnee AND m.dateDebut <= :finAnnee')
                ->setParameter('debutAnnee', new \DateTime(sprintf('%d-01-01', $annee)))
                ->setParameter('finAnnee', new \DateTime(sprintf('%d-12-31', $annee)));
        }

        /** @var Mission[] $missions */
        $missions = $qb->getQuery()->getResult();

        $stats = [
            'total' => count($missions),
            'brouillon' => 0,
            'soumises' => 0,
            'validees' => 0,
            'en_cours' => 0,
            'terminees' => 0,
            'cloturees' => 0,
            'annulees' => 0,
            'refusees' => 0,
            'total_jours' => 0,
            'budget_total' => 0.0,
        ];

        foreach ($missions as $m) {
            match ($m->getStatut()) {
                Mission::STATUT_BROUILLON => $stats['brouillon']++,
                Mission::STATUT_SOUMISE => $stats['soumises']++,
                Mission::STATUT_VALIDEE => $stats['validees']++,
                Mission::STATUT_EN_COURS => $stats['en_cours']++,
                Mission::STATUT_TERMINEE => $stats['terminees']++,
                Mission::STATUT_CLOTUREE => $stats['cloturees']++,
                Mission::STATUT_ANNULEE => $stats['annulees']++,
                Mission::STATUT_REFUSEE => $stats['refusees']++,
                default => null,
            };

            if (in_array($m->getStatut(), [Mission::STATUT_VALIDEE, Mission::STATUT_EN_COURS, Mission::STATUT_TERMINEE, Mission::STATUT_CLOTUREE], true)) {
                $stats['total_jours'] += $m->getDureeJours();
                if ($m->getBudgetEstime()) {
                    $stats['budget_total'] += (float) $m->getBudgetEstime();
                }
            }
        }

        return $stats;
    }
}
