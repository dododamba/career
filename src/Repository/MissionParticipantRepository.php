<?php

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\Mission;
use App\Entity\MissionParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MissionParticipant>
 *
 * @method MissionParticipant|null find($id, $lockMode = null, $lockVersion = null)
 * @method MissionParticipant|null findOneBy(array $criteria, array $orderBy = null)
 * @method MissionParticipant[]    findAll()
 * @method MissionParticipant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MissionParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MissionParticipant::class);
    }

    /**
     * @return MissionParticipant[]
     */
    public function findByMission(Mission $mission): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.mission = :mission')
            ->setParameter('mission', $mission)
            ->leftJoin('p.employe', 'e')
            ->orderBy('p.role', 'ASC')
            ->addOrderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MissionParticipant[]
     */
    public function findByEmploye(Employe $employe): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.employe = :employe')
            ->setParameter('employe', $employe)
            ->leftJoin('p.mission', 'm')
            ->orderBy('m.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
