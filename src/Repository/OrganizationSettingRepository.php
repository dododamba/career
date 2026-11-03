<?php

namespace App\Repository;

use App\Entity\OrganizationSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizationSetting>
 */
class OrganizationSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationSetting::class);
    }

    public function getPrimarySetting(): ?OrganizationSetting
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
