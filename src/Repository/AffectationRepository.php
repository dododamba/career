<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Employe;
use App\Entity\Institution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Affectation>
 */
class AffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affectation::class);
    }

    /**
     * Historique complet des affectations / mouvements d'un employé, par ordre antichronologique.
     *
     * @return Affectation[]
     */
    public function findHistoryByEmploye(Employe $employe): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.structure', 's')->addSelect('s')
            ->leftJoin('a.service', 'srv')->addSelect('srv')
            ->leftJoin('a.poste', 'p')->addSelect('p')
            ->leftJoin('a.fonction', 'f')->addSelect('f')
            ->leftJoin('a.ancienne_structure', 'as_struct')->addSelect('as_struct')
            ->leftJoin('a.ancien_service', 'as_srv')->addSelect('as_srv')
            ->leftJoin('a.ancien_poste', 'as_p')->addSelect('as_p')
            ->leftJoin('a.ancienne_fonction', 'as_f')->addSelect('as_f')
            ->where('a.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('a.date_effet', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'affectation active courante d'un employé
     */
    public function findActiveByEmploye(Employe $employe): ?Affectation
    {
        return $this->createQueryBuilder('a')
            ->where('a.employe = :employe')
            ->andWhere('a.statut = :statut')
            ->setParameter('employe', $employe)
            ->setParameter('statut', Affectation::STATUT_ACTIVE)
            ->orderBy('a.date_effet', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche les affectations d'une institution avec filtres optionnels
     *
     * @return Affectation[]
     */
    public function findByInstitution(
        Institution $institution,
        ?string $statut = null,
        ?string $type = null,
        int $limit = 100
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.employe', 'e')->addSelect('e')
            ->leftJoin('a.structure', 's')->addSelect('s')
            ->leftJoin('a.service', 'srv')->addSelect('srv')
            ->leftJoin('a.poste', 'p')->addSelect('p')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('a.date_effet', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit);

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('a.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($type !== null && $type !== '') {
            $qb->andWhere('a.type_mouvement = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les mouvements proposés en attente de validation
     *
     * @return Affectation[]
     */
    public function findEnAttenteValidation(Institution $institution): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.employe', 'e')->addSelect('e')
            ->leftJoin('a.structure', 's')->addSelect('s')
            ->leftJoin('a.poste', 'p')->addSelect('p')
            ->where('a.institution = :institution')
            ->andWhere('a.statut = :statut')
            ->setParameter('institution', $institution)
            ->setParameter('statut', Affectation::STATUT_PROPOSEE)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les mouvements validés en attente de prise de service
     *
     * @return Affectation[]
     */
    public function findEnAttentePriseService(Institution $institution): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.employe', 'e')->addSelect('e')
            ->leftJoin('a.structure', 's')->addSelect('s')
            ->leftJoin('a.poste', 'p')->addSelect('p')
            ->where('a.institution = :institution')
            ->andWhere('a.statut = :statut')
            ->setParameter('institution', $institution)
            ->setParameter('statut', Affectation::STATUT_VALIDEE)
            ->orderBy('a.date_effet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques globales des affectations pour une institution
     */
    public function getStatsByInstitution(Institution $institution): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.statut, COUNT(a.id) as nb')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution)
            ->groupBy('a.statut');

        $rows = $qb->getQuery()->getArrayResult();

        $stats = [
            'total' => 0,
            'proposees' => 0,
            'validees' => 0,
            'actives' => 0,
            'cloturees' => 0,
            'annulees' => 0,
        ];

        foreach ($rows as $row) {
            $count = (int) $row['nb'];
            $stats['total'] += $count;
            match ($row['statut']) {
                Affectation::STATUT_PROPOSEE => $stats['proposees'] = $count,
                Affectation::STATUT_VALIDEE => $stats['validees'] = $count,
                Affectation::STATUT_ACTIVE => $stats['actives'] = $count,
                Affectation::STATUT_CLOTUREE => $stats['cloturees'] = $count,
                Affectation::STATUT_ANNULEE => $stats['annulees'] = $count,
                default => null,
            };
        }

        return $stats;
    }
}
