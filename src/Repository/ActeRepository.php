<?php

namespace App\Repository;

use App\Entity\Acte;
use App\Entity\Affectation;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\TypeActe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Acte>
 */
class ActeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Acte::class);
    }

    public function save(Acte $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Acte $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Recherche avancée d'actes pour une institution avec filtres.
     *
     * @return Acte[]
     */
    public function findByInstitution(
        Institution $institution,
        ?int $typeActeId = null,
        ?string $statut = null,
        ?string $search = null,
        ?Employe $employe = null,
        ?int $annee = null,
        int $limit = 100
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.type_acte', 't')->addSelect('t')
            ->leftJoin('a.employe', 'e')->addSelect('e')
            ->leftJoin('a.structure', 's')->addSelect('s')
            ->leftJoin('a.poste', 'p')->addSelect('p')
            ->leftJoin('a.document', 'd')->addSelect('d')
            ->leftJoin('a.affectation', 'aff')->addSelect('aff')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('a.date_signature', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit);

        if ($typeActeId !== null && $typeActeId > 0) {
            $qb->andWhere('t.id = :typeId')
               ->setParameter('typeId', $typeActeId);
        }

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('a.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($employe !== null) {
            $qb->andWhere('a.employe = :employe')
               ->setParameter('employe', $employe);
        }

        if ($annee !== null && $annee > 1900) {
            $startDate = new \DateTime(sprintf('%d-01-01 00:00:00', $annee));
            $endDate = new \DateTime(sprintf('%d-12-31 23:59:59', $annee));
            $qb->andWhere('a.date_signature BETWEEN :start AND :end')
               ->setParameter('start', $startDate)
               ->setParameter('end', $endDate);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('(a.numero LIKE :search OR a.objet LIKE :search OR a.signataire_nom LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search OR e.matricule LIKE :search)')
               ->setParameter('search', '%' . trim($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Actes concernant un employé donné.
     *
     * @return Acte[]
     */
    public function findByEmploye(Employe $employe, ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.type_acte', 't')->addSelect('t')
            ->leftJoin('a.document', 'd')->addSelect('d')
            ->leftJoin('a.structure', 's')->addSelect('s')
            ->leftJoin('a.poste', 'p')->addSelect('p')
            ->where('a.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('a.date_signature', 'DESC');

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('a.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Actes associés à une affectation spécifique.
     *
     * @return Acte[]
     */
    public function findByAffectation(Affectation $affectation): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.type_acte', 't')->addSelect('t')
            ->leftJoin('a.document', 'd')->addSelect('d')
            ->where('a.affectation = :affectation')
            ->setParameter('affectation', $affectation)
            ->orderBy('a.date_signature', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un acte par son numéro officiel dans une institution.
     */
    public function findByNumero(string $numero, Institution $institution): ?Acte
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.type_acte', 't')->addSelect('t')
            ->where('a.numero = :numero')
            ->andWhere('a.institution = :institution')
            ->setParameter('numero', trim($numero))
            ->setParameter('institution', $institution)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Derniers actes enregistrés pour le tableau de bord.
     *
     * @return Acte[]
     */
    public function findRecent(Institution $institution, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.type_acte', 't')->addSelect('t')
            ->leftJoin('a.employe', 'e')->addSelect('e')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Métriques consolidées des actes administratifs d'une institution.
     */
    public function getStatsByInstitution(Institution $institution): array
    {
        $currentYear = (int) date('Y');
        $startOfYear = new \DateTime("$currentYear-01-01 00:00:00");
        $endOfYear = new \DateTime("$currentYear-12-31 23:59:59");

        // 1. Total actes
        $totalCount = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();

        // 2. Actes de l'année en cours
        $thisYearCount = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.institution = :institution')
            ->andWhere('a.date_signature BETWEEN :start AND :end')
            ->setParameter('institution', $institution)
            ->setParameter('start', $startOfYear)
            ->setParameter('end', $endOfYear)
            ->getQuery()
            ->getSingleScalarResult();

        // 3. Répartition par statut
        $statusRows = $this->createQueryBuilder('a')
            ->select('a.statut, COUNT(a.id) as nb')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution)
            ->groupBy('a.statut')
            ->getQuery()
            ->getArrayResult();

        $byStatus = [
            Acte::STATUT_PROJET => 0,
            Acte::STATUT_SIGNE => 0,
            Acte::STATUT_NOTIFIE => 0,
            Acte::STATUT_MODIFIE => 0,
            Acte::STATUT_ABROGE => 0,
            Acte::STATUT_ANNULE => 0,
        ];
        foreach ($statusRows as $row) {
            $byStatus[$row['statut']] = (int) $row['nb'];
        }

        // 4. Répartition par type d'acte
        $typeRows = $this->createQueryBuilder('a')
            ->select('t.id, t.libelle, t.code, COUNT(a.id) as nb')
            ->leftJoin('a.type_acte', 't')
            ->where('a.institution = :institution')
            ->setParameter('institution', $institution)
            ->groupBy('t.id, t.libelle, t.code')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return [
            'total_actes' => $totalCount,
            'actes_annee' => $thisYearCount,
            'annee' => $currentYear,
            'en_vigueur' => ($byStatus[Acte::STATUT_SIGNE] ?? 0) + ($byStatus[Acte::STATUT_NOTIFIE] ?? 0),
            'projets' => $byStatus[Acte::STATUT_PROJET] ?? 0,
            'abroges_modifies' => ($byStatus[Acte::STATUT_ABROGE] ?? 0) + ($byStatus[Acte::STATUT_MODIFIE] ?? 0) + ($byStatus[Acte::STATUT_ANNULE] ?? 0),
            'by_statut' => $byStatus,
            'by_type' => $typeRows,
        ];
    }
}
