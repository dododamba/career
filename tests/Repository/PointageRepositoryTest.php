<?php

namespace App\Tests\Repository;

use App\Entity\Institution;
use App\Entity\Pointage;
use App\Entity\Service;
use App\Repository\PointageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PointageRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private PointageRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->repository = $this->em->getRepository(Pointage::class);
    }

    public function testGetStatsByServiceAndInstitutionReturnsEmptyArrayWhenNoData(): void
    {
        $institution = new Institution();
        $institution->setNom('Test Institution');
        $institution->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($institution);
        $this->em->flush();

        $stats = $this->repository->getStatsByServiceAndInstitution($institution);
        $this->assertIsArray($stats);

        $presenceRate = $this->repository->getPresenceRateByMonthAndInstitution($institution, 6);
        $this->assertIsArray($presenceRate);
        $this->assertEmpty($presenceRate);

        $topAbsences = $this->repository->getTopAbsencesByServiceAndInstitution($institution, 5);
        $this->assertIsArray($topAbsences);
        $this->assertEmpty($topAbsences);

        // Cleanup
        $this->em->remove($institution);
        $this->em->flush();
    }
}
