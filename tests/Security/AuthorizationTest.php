<?php

namespace App\Tests\Security;

use App\Entity\Conges;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AuthorizationTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);

        $this->institution = new Institution();
        $this->institution->setNom('Authz Test Org ' . uniqid());
        $this->institution->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($this->institution);
        $this->em->flush();
    }

    private function createUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, 'TestPassword123!'));
        $user->setRoles($roles);
        $user->setFirstname('Authz');
        $user->setLastname('Tester');
        $user->setIsVerified(true);
        $user->setInstitution($this->institution);
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testAnonymousRedirectedToLoginFromSecuredPages(): void
    {
        $this->client->request('GET', '/admin/user');
        $this->assertResponseRedirects('/login');

        $this->client->request('GET', '/admin/institution');
        $this->assertResponseRedirects('/login');

        $this->client->request('GET', '/log');
        $this->assertResponseRedirects('/login');

        $this->client->request('GET', '/dashboard');
        $this->assertResponseRedirects('/login');
    }

    public function testRoleUserForbiddenFromAdminAndLogs(): void
    {
        $user = $this->createUser('role_user_' . uniqid() . '@example.com', ['ROLE_USER']);
        $this->client->loginUser($user);

        // ROLE_USER forbidden on /admin/user
        $this->client->request('GET', '/admin/user');
        $this->assertResponseStatusCodeSame(403);

        // ROLE_USER forbidden on /admin/institution
        $this->client->request('GET', '/admin/institution');
        $this->assertResponseStatusCodeSame(403);

        // ROLE_USER forbidden on /log
        $this->client->request('GET', '/log');
        $this->assertResponseStatusCodeSame(403);

        // ROLE_USER allowed on /dashboard
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
    }

    public function testRoleAdminAllowedToAdminAndLogs(): void
    {
        $admin = $this->createUser('role_admin_' . uniqid() . '@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/user');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/institution');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/log');
        $this->assertResponseIsSuccessful();
    }

    public function testUserVoterPreventsSelfDeletionAndSelfDeactivation(): void
    {
        $admin = $this->createUser('admin_voter_' . uniqid() . '@example.com', ['ROLE_ADMIN']);
        $otherUser = $this->createUser('target_voter_' . uniqid() . '@example.com', ['ROLE_USER']);

        $this->client->loginUser($admin);

        /** @var AuthorizationCheckerInterface $authChecker */
        $authChecker = $this->client->getContainer()->get('security.authorization_checker');

        // Admin can view other user and self
        $this->assertTrue($authChecker->isGranted('USER_VIEW', $otherUser));
        $this->assertTrue($authChecker->isGranted('USER_VIEW', $admin));

        // Admin CAN delete other user in same institution
        $this->assertTrue($authChecker->isGranted('USER_DELETE', $otherUser));

        // Admin CANNOT delete themselves (CRITICAL PROTECTION)
        $this->assertFalse($authChecker->isGranted('USER_DELETE', $admin));

        // Admin CAN toggle status of other user
        $this->assertTrue($authChecker->isGranted('USER_TOGGLE_STATUS', $otherUser));

        // Admin CANNOT toggle their own status (CRITICAL SELF-LOCKOUT PROTECTION)
        $this->assertFalse($authChecker->isGranted('USER_TOGGLE_STATUS', $admin));
    }

    public function testCongesVoterPreventsSelfApproval(): void
    {
        $managerUser = $this->createUser('manager_' . uniqid() . '@example.com', ['ROLE_MANAGER']);
        $agentUser = $this->createUser('agent_' . uniqid() . '@example.com', ['ROLE_USER']);

        $managerEmploye = new Employe();
        $managerEmploye->setUser($managerUser);
        $managerEmploye->setNom('Manager');
        $managerEmploye->setPrenom('Test');
        $managerEmploye->setInstitution($this->institution);
        $managerEmploye->setMatricule('MAT-MGR-' . uniqid());
        $managerEmploye->setSalaire(4500.0);
        $this->em->persist($managerEmploye);

        $agentEmploye = new Employe();
        $agentEmploye->setUser($agentUser);
        $agentEmploye->setNom('Agent');
        $agentEmploye->setPrenom('Test');
        $agentEmploye->setInstitution($this->institution);
        $agentEmploye->setMatricule('MAT-AGT-' . uniqid());
        $agentEmploye->setSalaire(3000.0);
        $this->em->persist($agentEmploye);

        $typeConge = new \App\Entity\TypeConges();
        $typeConge->setLibelle('Annuel');
        $typeConge->setIsDeductible(true);
        $this->em->persist($typeConge);

        // Leave request created for the manager
        $managerConge = new Conges();
        $managerConge->setEmploye($managerEmploye);
        $managerConge->setDateDebut(new \DateTime('tomorrow'));
        $managerConge->setDateFin(new \DateTime('+5 days'));
        $managerConge->setDateDiff(4);
        $managerConge->setTypeConge($typeConge);
        $managerConge->setMotif('Vacation');
        $managerConge->setStatut(0); // Pending
        $managerConge->setCreatedAt(new \DateTimeImmutable());
        $managerConge->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($managerConge);

        // Leave request created for the agent
        $agentConge = new Conges();
        $agentConge->setEmploye($agentEmploye);
        $agentConge->setDateDebut(new \DateTime('tomorrow'));
        $agentConge->setDateFin(new \DateTime('+5 days'));
        $agentConge->setDateDiff(4);
        $agentConge->setTypeConge($typeConge);
        $agentConge->setMotif('Doctor');
        $agentConge->setStatut(0); // Pending
        $agentConge->setCreatedAt(new \DateTimeImmutable());
        $agentConge->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($agentConge);

        $this->em->flush();

        $this->client->loginUser($managerUser);

        /** @var AuthorizationCheckerInterface $authChecker */
        $authChecker = $this->client->getContainer()->get('security.authorization_checker');

        // Manager CAN approve leave of agent in same institution
        $this->assertTrue($authChecker->isGranted('CONGES_APPROVE', $agentConge));

        // Manager CANNOT approve their own leave (CRITICAL SEPARATION OF CONCERNS)
        $this->assertFalse($authChecker->isGranted('CONGES_APPROVE', $managerConge));

        // Manager CANNOT reject their own leave
        $this->assertFalse($authChecker->isGranted('CONGES_REJECT', $managerConge));
    }
}
