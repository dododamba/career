<?php

namespace App\Repository;

use App\Entity\Institution;
use App\Entity\Poste;
use App\Entity\Structure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Poste>
 */
class PosteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Poste::class);
    }

    /**
     * Recherche les postes vacants dans une institution (avec filtre optionnel par structure)
     */
    public function findVacantsByInstitution(Institution $institution, ?Structure $structure = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.institution = :institution')
            ->andWhere('p.statut = :statutVacant OR p.occupant_actuel IS NULL')
            ->andWhere('p.statut NOT IN (:inactifs)')
            ->setParameter('institution', $institution)
            ->setParameter('statutVacant', Poste::STATUT_VACANT)
            ->setParameter('inactifs', [Poste::STATUT_FERME, Poste::STATUT_SUSPENDU])
            ->orderBy('p.intitule', 'ASC');

        if ($structure !== null) {
            $qb->andWhere('p.structure = :structure')
               ->setParameter('structure', $structure);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche les postes occupés dans une institution
     */
    public function findOccupesByInstitution(Institution $institution): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.institution = :institution')
            ->andWhere('p.statut = :statutOccupe')
            ->andWhere('p.occupant_actuel IS NOT NULL')
            ->setParameter('institution', $institution)
            ->setParameter('statutOccupe', Poste::STATUT_OCCUPE)
            ->orderBy('p.intitule', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les métriques et KPIs des postes pour une institution
     */
    public function getPostesStatsByInstitution(Institution $institution): array
    {
        $postes = $this->createQueryBuilder('p')
            ->where('p.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getResult();

        $total = count($postes);
        $vacants = 0;
        $occupes = 0;
        $suspendus = 0;
        $fermes = 0;

        foreach ($postes as $p) {
            if ($p->getStatut() === Poste::STATUT_FERME) {
                $fermes++;
            } elseif ($p->getStatut() === Poste::STATUT_SUSPENDU) {
                $suspendus++;
            } elseif ($p->isOccupe()) {
                $occupes++;
            } else {
                $vacants++;
            }
        }

        $actifs = $total - $fermes;
        $tauxOccupation = $actifs > 0 ? (int) round(($occupes / $actifs) * 100) : 0;

        return [
            'total' => $total,
            'actifs' => $actifs,
            'vacants' => $vacants,
            'occupes' => $occupes,
            'suspendus' => $suspendus,
            'fermes' => $fermes,
            'tauxOccupation' => $tauxOccupation,
        ];
    }

    /**
     * Recherche filtrée de postes
     */
    public function searchPostes(Institution $institution, ?string $query = null, ?string $statut = null, ?int $structureId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.structure', 'st')
            ->leftJoin('p.service', 's')
            ->leftJoin('p.fonction', 'f')
            ->leftJoin('p.occupant_actuel', 'e')
            ->addSelect('st', 's', 'f', 'e')
            ->where('p.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('p.intitule', 'ASC');

        if ($query !== null && trim($query) !== '') {
            $qb->andWhere('LOWER(p.intitule) LIKE :query OR LOWER(p.reference) LIKE :query OR LOWER(e.nom) LIKE :query OR LOWER(e.prenom) LIKE :query')
               ->setParameter('query', '%' . strtolower(trim($query)) . '%');
        }

        if ($statut !== null && trim($statut) !== '' && $statut !== 'ALL') {
            if ($statut === Poste::STATUT_VACANT) {
                $qb->andWhere('(p.statut = :stVacant OR p.occupant_actuel IS NULL) AND p.statut NOT IN (:inactifs)')
                   ->setParameter('stVacant', Poste::STATUT_VACANT)
                   ->setParameter('inactifs', [Poste::STATUT_FERME, Poste::STATUT_SUSPENDU]);
            } else {
                $qb->andWhere('p.statut = :statut')
                   ->setParameter('statut', $statut);
            }
        }

        if ($structureId !== null && $structureId > 0) {
            $qb->andWhere('p.structure = :structureId')
               ->setParameter('structureId', $structureId);
        }

        return $qb->getQuery()->getResult();
    }
}
