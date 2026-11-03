<?php

namespace App\Tests\Entity;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Pointage;
use App\Entity\SoldeConge;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EmployeRelationsTest extends TestCase
{
    public function testEmployeUserRelation(): void
    {
        $employe = new Employe();
        $user = new User();

        $employe->setUser($user);
        $this->assertSame($user, $employe->getUser());

        $user->addEmploye($employe);
        $this->assertTrue($user->getEmployes()->contains($employe));
        $this->assertSame($user, $employe->getUser());

        $user->removeEmploye($employe);
        $this->assertFalse($user->getEmployes()->contains($employe));
    }

    public function testEmployeInstitutionRelation(): void
    {
        $employe = new Employe();
        $institution = new Institution();

        $employe->setInstitution($institution);
        $this->assertSame($institution, $employe->getInstitution());

        $institution->addEmploye($employe);
        $this->assertTrue($institution->getEmployes()->contains($employe));
        $this->assertSame($institution, $employe->getInstitution());

        $institution->removeEmploye($employe);
        $this->assertFalse($institution->getEmployes()->contains($employe));
        $this->assertNull($employe->getInstitution());
    }

    public function testEmployeSoldeCongeRelation(): void
    {
        $employe = new Employe();
        $solde = new SoldeConge();

        $this->assertCount(0, $employe->getSoldesConges());

        $employe->addSoldeConge($solde);
        $this->assertCount(1, $employe->getSoldesConges());
        $this->assertTrue($employe->getSoldesConges()->contains($solde));
        $this->assertSame($employe, $solde->getEmploye());

        $employe->removeSoldeConge($solde);
        $this->assertCount(0, $employe->getSoldesConges());
        $this->assertFalse($employe->getSoldesConges()->contains($solde));
        $this->assertNull($solde->getEmploye());
    }

    public function testPointageInitialization(): void
    {
        $pointage = new Pointage();

        $this->assertTrue($pointage->isEstPresent());
        $this->assertInstanceOf(\DateTimeInterface::class, $pointage->getDate());
        $this->assertInstanceOf(\DateTimeInterface::class, $pointage->getDateEntree());
    }
}
