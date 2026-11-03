<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Recherche avancée de documents pour une institution avec filtres.
     *
     * @return Document[]
     */
    public function findByInstitution(
        Institution $institution,
        ?string $categorie = null,
        ?string $statut = null,
        ?string $search = null,
        ?Employe $employe = null,
        int $limit = 100
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.employe', 'e')->addSelect('e')
            ->leftJoin('d.affectation', 'aff')->addSelect('aff')
            ->leftJoin('d.ajoute_par', 'u')->addSelect('u')
            ->where('d.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($categorie !== null && $categorie !== '') {
            $qb->andWhere('d.categorie = :categorie')
               ->setParameter('categorie', $categorie);
        }

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('d.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($employe !== null) {
            $qb->andWhere('d.employe = :employe')
               ->setParameter('employe', $employe);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('(d.titre LIKE :search OR d.reference LIKE :search OR d.fichier_nom_original LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search OR e.matricule LIKE :search)')
               ->setParameter('search', '%' . trim($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Documents d'un agent donné, triés du plus récent au plus ancien.
     *
     * @return Document[]
     */
    public function findByEmploye(Employe $employe, ?string $categorie = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.affectation', 'aff')->addSelect('aff')
            ->where('d.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('d.createdAt', 'DESC');

        if ($categorie !== null && $categorie !== '') {
            $qb->andWhere('d.categorie = :categorie')
               ->setParameter('categorie', $categorie);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Documents associés à un mouvement / affectation.
     *
     * @return Document[]
     */
    public function findByAffectation(Affectation $affectation): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.affectation = :affectation')
            ->setParameter('affectation', $affectation)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Métriques consolidées du coffre-fort documentaire d'une institution.
     */
    public function getStatsByInstitution(Institution $institution): array
    {
        // 1. Total documents et volume de stockage en octets
        $totals = $this->createQueryBuilder('d')
            ->select('COUNT(d.id) as total_docs, SUM(d.taille) as total_taille')
            ->where('d.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleResult();

        $totalDocs = (int) ($totals['total_docs'] ?? 0);
        $totalBytes = (int) ($totals['total_taille'] ?? 0);

        // 2. Répartition par catégorie
        $byCategoryRows = $this->createQueryBuilder('d')
            ->select('d.categorie, COUNT(d.id) as nb, SUM(d.taille) as taille')
            ->where('d.institution = :institution')
            ->setParameter('institution', $institution)
            ->groupBy('d.categorie')
            ->getQuery()
            ->getArrayResult();

        $byCategory = [];
        foreach ($byCategoryRows as $row) {
            $byCategory[$row['categorie']] = [
                'count' => (int) $row['nb'],
                'taille' => (int) ($row['taille'] ?? 0),
            ];
        }

        // 3. Répartition par statut
        $byStatusRows = $this->createQueryBuilder('d')
            ->select('d.statut, COUNT(d.id) as nb')
            ->where('d.institution = :institution')
            ->setParameter('institution', $institution)
            ->groupBy('d.statut')
            ->getQuery()
            ->getArrayResult();

        $byStatut = [];
        foreach ($byStatusRows as $row) {
            $byStatut[$row['statut']] = (int) $row['nb'];
        }

        // 4. Nombre de documents restreints/confidentiels
        $confidentialCount = (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.institution = :institution')
            ->andWhere('d.niveau_confidentialite IN (:conf)')
            ->setParameter('institution', $institution)
            ->setParameter('conf', [Document::CONFIDENTIALITE_CONFIDENTIEL, Document::CONFIDENTIALITE_SECRET_RH])
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_docs' => $totalDocs,
            'total_bytes' => $totalBytes,
            'total_taille_formatee' => $this->formatBytes($totalBytes),
            'valides' => $byStatut[Document::STATUT_VALIDE] ?? 0,
            'en_attente' => $byStatut[Document::STATUT_EN_ATTENTE] ?? 0,
            'archives' => $byStatut[Document::STATUT_ARCHIVE] ?? 0,
            'confidentiels' => $confidentialCount,
            'by_category' => $byCategory,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' Go';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' Mo';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' Ko';
        }
        return $bytes . ' octets';
    }
}
