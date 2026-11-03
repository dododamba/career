<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Employe;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Service\SecurityAuditService;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, EmployeRepository $employeRepository): Response
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $institution = $currentUser->getInstitution();
        if (!$institution) {
            $this->addFlash('error', 'Votre compte n\'est associé à aucune institution.');
            return $this->render('user/index.html.twig', [
                'users' => [],
                'stats' => [
                    'total' => 0,
                    'verified' => 0,
                    'admins' => 0,
                    'managers' => 0,
                    'recentUsers' => 0,
                ],
                'institution' => null,
            ]);
        }

        // Récupérer seulement les utilisateurs de la même institution
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.institution = :institution')
            ->setParameter('institution', $institution)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $totalUsers = count($users);
        
        // Statistiques pour le dashboard filtrées par institution
        $stats = [
            'total' => $totalUsers,
            'verified' => count(array_filter($users, fn($user) => $user->isVerified())),
            'admins' => count(array_filter($users, fn($user) => in_array('ROLE_ADMIN', $user->getRoles()))),
            'managers' => count(array_filter($users, fn($user) => in_array('ROLE_MANAGER', $user->getRoles()))),
            'recentUsers' => count(array_filter($users, fn($user) => $user->getCreatedAt() > new \DateTimeImmutable('-30 days'))),
        ];

        // Compter les employés liés dans cette institution
        $employesWithUser = $employeRepository->createQueryBuilder('e')
            ->join('e.user', 'u')
            ->where('u.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getResult();

        $stats['withEmploye'] = count($employesWithUser);
        $stats['withoutEmploye'] = $totalUsers - $stats['withEmploye'];

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'institution' => $institution,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SecurityAuditService $auditService
    ): Response {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            $this->addFlash('error', 'Vous devez être connecté pour créer un utilisateur.');
            return $this->redirectToRoute('app_login');
        }

        $institution = $currentUser->getInstitution();
        if (!$institution) {
            $this->addFlash('error', 'Votre compte n\'est associé à aucune institution. Contactez l\'administrateur.');
            return $this->redirectToRoute('app_user_index');
        }

        $user = new User();
        // Associer automatiquement l'institution de l'utilisateur connecté
        $user->setInstitution($institution);
        
        $form = $this->createForm(UserType::class, $user, [
            'institution' => $institution
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Vérifier l'unicité de l'email dans l'institution
                $existingUser = $entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->where('u.email = :email')
                    ->andWhere('u.institution = :institution')
                    ->setParameter('email', $user->getEmail())
                    ->setParameter('institution', $institution)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($existingUser) {
                    $this->addFlash('error', 'Un utilisateur avec cet email existe déjà dans votre institution.');
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form,
                        'institution' => $institution,
                    ]);
                }

                // Hasher le mot de passe
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                );
                $user->setPassword($hashedPassword);
                
                // Créer un compte employé associé automatiquement
                $employe = new Employe();
                if ($user->getFirstname() && $user->getLastname()) {
                    $employe->setPrenom($user->getFirstname());
                    $employe->setNom($user->getLastname());
                }
                $employe->setEmail($user->getEmail());
                
                // Générer un matricule unique pour l'institution
                $matricule = $this->generateMatricule($entityManager, $institution);
                $employe->setMatricule($matricule);
                
                $employe->setStatut(true);
                $employe->setDateEmbauche(new \DateTime());
                $employe->setSalaire(0); // Valeur par défaut, à modifier
                $employe->setActif(true);
                
                // Associer l'utilisateur et l'employé
                $employe->setUser($user);
                $employe->setInstitution($institution);

                $entityManager->persist($user);
                $entityManager->persist($employe);
                $entityManager->flush();

                $auditService->logAccountEnabled($user, $currentUser, $request);

                $this->addFlash('success', sprintf(
                    'Utilisateur %s créé avec succès dans l\'institution %s !',
                    $user->getFullName(),
                    $institution->getNom()
                ));
                
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création de l\'utilisateur : ' . $e->getMessage());
            }
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'institution' => $institution,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted('USER_VIEW', $user);

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SecurityAuditService $auditService
    ): Response {
        $this->denyAccessUnlessGranted('USER_EDIT', $user);

        $currentUser = $this->getUser();
        $oldRoles = $user->getRoles();

        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
            'institution' => $user->getInstitution()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Mise à jour du mot de passe si fourni
                if ($password = $form->get('password')->getData()) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                }
                
                // Synchroniser les informations de base avec l'employé
                $employes = $entityManager->getRepository(Employe::class)
                    ->findBy(['user' => $user]);
                
                foreach ($employes as $employe) {
                    $employe->setPrenom($user->getFirstname());
                    $employe->setNom($user->getLastname());
                    $employe->setEmail($user->getEmail());
                    $entityManager->persist($employe);
                }
                
                if (empty($employes)) {
                    // Créer un employé si l'utilisateur n'en a pas
                    $employe = new Employe();
                    $employe->setPrenom($user->getFirstname());
                    $employe->setNom($user->getLastname());
                    $employe->setEmail($user->getEmail());
                    $employe->setMatricule($this->generateMatricule($entityManager, $user->getInstitution()));
                    $employe->setStatut(true);
                    $employe->setDateEmbauche(new \DateTime());
                    $employe->setSalaire(0);
                    $employe->setActif(true);
                    $employe->setUser($user);
                    
                    $entityManager->persist($employe);
                }
                
                $entityManager->flush();

                if ($oldRoles !== $user->getRoles()) {
                    $auditService->logRoleChanged($user, $oldRoles, $user->getRoles(), $currentUser, $request);
                }
                
                $this->addFlash('success', 'Utilisateur mis à jour avec succès!');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager,
        SecurityAuditService $auditService
    ): Response {
        $this->denyAccessUnlessGranted('USER_DELETE', $user);
        $currentUser = $this->getUser();

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $auditService->logAccountDisabled($user, $currentUser, $request);

                // Les employés associés seront supprimés automatiquement via la relation
                $entityManager->remove($user);
                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur supprimé avec succès!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cet utilisateur : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager,
        SecurityAuditService $auditService
    ): Response {
        $this->denyAccessUnlessGranted('USER_TOGGLE_STATUS', $user);
        $currentUser = $this->getUser();

        if ($this->isCsrfTokenValid('toggle-status'.$user->getId(), $request->getPayload()->getString('_token'))) {
            try {
                // Changer le statut de vérification
                $user->setIsVerified(!$user->isVerified());
                
                // Synchroniser le statut avec les employés associés
                $employes = $entityManager->getRepository(Employe::class)
                    ->findBy(['user' => $user]);
                
                foreach ($employes as $employe) {
                    $employe->setStatut($user->isVerified());
                    $employe->setActif($user->isVerified());
                    $entityManager->persist($employe);
                }
                
                $entityManager->flush();

                if ($user->isVerified()) {
                    $auditService->logAccountEnabled($user, $currentUser, $request);
                } else {
                    $auditService->logAccountDisabled($user, $currentUser, $request);
                }

                $this->addFlash('success', 'Statut modifié avec succès!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du statut : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Génère un matricule unique pour l'institution
     */
    private function generateMatricule(EntityManagerInterface $entityManager, $institution): string
    {
        $prefix = strtoupper(substr($institution->getNom(), 0, 3));
        $year = date('Y');
        
        // Compte le nombre d'employés dans l'institution pour cette année
        $count = $entityManager->getRepository(Employe::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.user', 'u')
            ->where('u.institution = :institution')
            ->andWhere('YEAR(e.date_embauche) = :year')
            ->setParameter('institution', $institution)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
        
        $nextNumber = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        return $prefix . $year . $nextNumber;
    }
}