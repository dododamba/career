<?php

namespace App\Repository;

use App\Entity\Absence;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\TypeAbsence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Absence>
 *
 * @method Absence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Absence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Absence[]    findAll()
 * @method Absence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Absence::class);
    }

    /**
     * Recherche d'éventuels chevauchements avec des absences existantes (en attente ou approuvées)
     *
     * @return Absence[]
     */
    public function findOverlapping(
        Employe $employe,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->where('a.employe = :employe')
            ->andWhere('a.statut IN (:statuts)')
            ->andWhere('a.dateDebut <= :dateFin')
            ->andWhere('a.dateFin >= :dateDebut')
            ->setParameter('employe', $employe)
            ->setParameter('statuts', [Absence::STATUT_EN_ATTENTE, Absence::STATUT_APPROUVE])
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'));

        if ($excludeId !== null) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcule la somme des durées d'absence prises par un agent pour un type donné durant une année.
     */
    public function getDureeCumuleeParAnnee(
        Employe $employe,
        TypeAbsence $typeAbsence,
        int $annee,
        ?int $excludeId = null
    ): float {
        $debutAnnee = new \DateTimeImmutable(sprintf('%d-01-01', $annee));
        $finAnnee = new \DateTimeImmutable(sprintf('%d-12-31', $annee));

        $qb = $this->createQueryBuilder('a')
            ->select('SUM(a.duree)')
            ->where('a.employe = :employe')
            ->andWhere('a.typeAbsence = :typeAbsence')
            ->andWhere('a.statut IN (:statuts)')
            ->andWhere('a.dateDebut >= :debutAnnee')
            ->andWhere('a.dateDebut <= :finAnnee')
            ->setParameter('employe', $employe)
            ->setParameter('typeAbsence', $typeAbsence)
            ->setParameter('statuts', [Absence::STATUT_EN_ATTENTE, Absence::STATUT_APPROUVE])
            ->setParameter('debutAnnee', $debutAnnee)
            ->setParameter('finAnnee', $finAnnee);

        if ($excludeId !== null) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    /**
     * Recherche avec filtres optionnels pour une institution.
     *
     * @return Absence[]
     */
    public function findByFilters(
        Institution $institution,
        ?int $statut = null,
        ?TypeAbsence $typeAbsence = null,
        ?Employe $employe = null,
        ?string $search = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.employe', 'e')
            ->leftJoin('a.typeAbsence', 't')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('a.createdAt', 'DESC');

        if ($statut !== null) {
            $qb->andWhere('a.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($typeAbsence !== null) {
            $qb->andWhere('a.typeAbsence = :typeAbsence')
                ->setParameter('typeAbsence', $typeAbsence);
        }

        if ($employe !== null) {
            $qb->andWhere('a.employe = :employe')
                ->setParameter('employe', $employe);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('e.nom LIKE :search OR e.prenom LIKE :search OR a.motif LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques globales des absences pour une institution.
     */
    public function getStatistiques(Institution $institution, ?int $annee = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution);

        if ($annee !== null) {
            $qb->andWhere('a.dateDebut >= :debutAnnee AND a.dateDebut <= :finAnnee')
                ->setParameter('debutAnnee', new \DateTime(sprintf('%d-01-01', $annee)))
                ->setParameter('finAnnee', new \DateTime(sprintf('%d-12-31', $annee)));
        }

        /** @var Absence[] $absences */
        $absences = $qb->getQuery()->getResult();

        $stats = [
            'total' => count($absences),
            'en_attente' => 0,
            'accordees' => 0,
            'refusees' => 0,
            'annulees' => 0,
            'total_jours_accordes' => 0.0,
            'total_heures_accordees' => 0.0,
        ];

        foreach ($absences as $absence) {
            match ($absence->getStatut()) {
                Absence::STATUT_EN_ATTENTE => $stats['en_attente']++,
                Absence::STATUT_APPROUVE => [
                    $stats['accordees']++,
                    $absence->getUnite() === TypeAbsence::UNITE_HEURES
                        ? $stats['total_heures_accordees'] += (float) $absence->getDuree()
                        : $stats['total_jours_accordes'] += (float) $absence->getDuree(),
                ],
                Absence::STATUT_REFUSE => $stats['refusees']++,
                Absence::STATUT_ANNULE => $stats['annulees']++,
                default => null,
            };
        }

        return $stats;
    }
}
