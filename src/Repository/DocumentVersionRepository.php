<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\DocumentVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentVersion>
 */
class DocumentVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentVersion::class);
    }

    /**
     * Versions d'un document données par ordre antichronologique
     *
     * @return DocumentVersion[]
     */
    public function findByDocument(Document $document): array
    {
        return $this->createQueryBuilder('dv')
            ->leftJoin('dv.cree_par', 'u')->addSelect('u')
            ->where('dv.document = :document')
            ->setParameter('document', $document)
            ->orderBy('dv.numero_version', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
