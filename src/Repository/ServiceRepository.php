<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Institution;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /**
     * Compte le nombre de services d'une institution
     */
    public function countByInstitution(Institution $institution): int
    {
        return $this->count(['institution' => $institution]);
    }

    /**
     * Trouve les services par institution
     */
    public function findByInstitution(Institution $institution): array
    {
        return $this->findBy(['institution' => $institution], ['nom' => 'ASC']);
    }
}
