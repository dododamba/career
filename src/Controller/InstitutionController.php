<?php

namespace App\Controller;

use App\Entity\Institution;
use App\Entity\OrganisationHistory;
use App\Entity\TypeInstitution;
use App\Form\InstitutionType;
use App\Repository\EmployeRepository;
use App\Repository\InstitutionRepository;
use App\Repository\OrganisationHistoryRepository;
use App\Repository\ServiceRepository;
use App\Repository\StructureRepository;
use App\Repository\TypeInstitutionRepository;
use App\Service\OrganisationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/institution')]
final class InstitutionController extends AbstractController
{
    #[Route(name: 'app_institution_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        InstitutionRepository $institutionRepository,
        TypeInstitutionRepository $typeInstitutionRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getUser();
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');

        // Gestion de la pagination
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Récupération des institutions avec pagination et eager loading
        if (!$isSuperAdmin && $user && $user->getInstitution()) {
            $dql = "
                SELECT i, t, e, s, u
                FROM App\Entity\Institution i
                LEFT JOIN i.typeInstitution t
                LEFT JOIN i.employes e
                LEFT JOIN i.services s
                LEFT JOIN i.users u
                WHERE i.id = :instId
                ORDER BY i.nom ASC
            ";
            $query = $entityManager->createQuery($dql)
                ->setParameter('instId', $user->getInstitution()->getId())
                ->setMaxResults($limit)
                ->setFirstResult($offset);
            $institutions = $query->getResult();
            $totalInstitutions = count($institutions);
            $pagesCount = 1;
        } else {
            $dql = "
                SELECT i, t, e, s, u
                FROM App\Entity\Institution i
                LEFT JOIN i.typeInstitution t
                LEFT JOIN i.employes e
                LEFT JOIN i.services s
                LEFT JOIN i.users u
                ORDER BY i.nom ASC
            ";
            $query = $entityManager->createQuery($dql)
                ->setMaxResults($limit)
                ->setFirstResult($offset);
            $institutions = $query->getResult();
            $totalInstitutions = $institutionRepository->count([]);
            $pagesCount = (int) ceil($totalInstitutions / $limit);
        }
        
        // Récupération de tous les types pour le filtre et le formulaire
        $typesInstitution = $typeInstitutionRepository->findAll();
        
        // Calcul des statistiques avec requêtes optimisées
        $stats = $this->calculateStatistics($institutionRepository, $entityManager);
        
        // Calcul des statistiques par institution pour les graphiques
        $employeesByInstitution = [];
        $servicesByInstitution = [];
        
        // Requête optimisée pour compter les employés et services par institution
        $statsQuery = $entityManager->createQuery('
            SELECT i.id, i.nom,
                   COUNT(DISTINCT e.id) as employee_count,
                   COUNT(DISTINCT s.id) as service_count,
                   COUNT(DISTINCT u.id) as user_count
            FROM App\Entity\Institution i
            LEFT JOIN i.employes e
            LEFT JOIN i.services s
            LEFT JOIN i.users u
            GROUP BY i.id, i.nom
            ORDER BY employee_count DESC
        ');
        
        $institutionStats = $statsQuery->getResult();
        
        // Préparation des données pour les graphiques (top 5)
        $topEmployees = array_slice($institutionStats, 0, 5);
        foreach ($topEmployees as $stat) {
            if ($stat['employee_count'] > 0) {
                $employeesByInstitution[$stat['nom']] = $stat['employee_count'];
            }
        }
        
        // Tri par services et sélection du top 5
        usort($institutionStats, function($a, $b) {
            return $b['service_count'] - $a['service_count'];
        });
        $topServices = array_slice($institutionStats, 0, 5);
        foreach ($topServices as $stat) {
            if ($stat['service_count'] > 0) {
                $servicesByInstitution[$stat['nom']] = $stat['service_count'];
            }
        }
        
        // Gestion du formulaire d'ajout d'institution inline
        if ($request->isMethod('POST') && $request->request->has('institution')) {
            $this->handleInstitutionForm($request, $entityManager, $typeInstitutionRepository);
            return $this->redirectToRoute('app_institution_index');
        }
        
        // Gestion du formulaire d'ajout de type inline
        if ($request->isMethod('POST') && $request->request->has('type_institution')) {
            $this->handleTypeForm($request, $entityManager, $slugger);
            return $this->redirectToRoute('app_institution_index');
        }
        
        return $this->render('institution/index.html.twig', [
            'institutions' => $institutions,
            'types_institution' => $typesInstitution,
            'stats' => $stats,
            'employees_by_institution' => $employeesByInstitution,
            'services_by_institution' => $servicesByInstitution,
            'page' => $page,
            'pages_count' => $pagesCount,
        ]);
    }
    
    private function calculateStatistics(InstitutionRepository $institutionRepository, EntityManagerInterface $entityManager): array
    {
        $stats = [
            'total_institutions' => 0,
            'total_employes' => 0,
            'total_services' => 0,
            'total_types' => 0,
            'total_users' => 0,
        ];
        
        try {
            // Requêtes optimisées pour éviter le lazy loading
            $stats['total_institutions'] = $institutionRepository->count([]);
            
            $stats['total_employes'] = $entityManager->createQuery('
                SELECT COUNT(e.id) FROM App\Entity\Employe e
            ')->getSingleScalarResult();
            
            $stats['total_services'] = $entityManager->createQuery('
                SELECT COUNT(s.id) FROM App\Entity\Service s
            ')->getSingleScalarResult();
            
            $stats['total_types'] = $entityManager->createQuery('
                SELECT COUNT(t.id) FROM App\Entity\TypeInstitution t
            ')->getSingleScalarResult();
            
            $stats['total_users'] = $entityManager->createQuery('
                SELECT COUNT(u.id) FROM App\Entity\User u
            ')->getSingleScalarResult();
            
        } catch (\Exception $e) {
            // En cas d'erreur, on garde les valeurs par défaut
            $this->addFlash('warning', 'Certaines statistiques n\'ont pas pu être chargées.');
        }
        
        return $stats;
    }
    
    private function handleInstitutionForm(
        Request $request,
        EntityManagerInterface $entityManager,
        TypeInstitutionRepository $typeInstitutionRepository
    ): void {
        $institutionData = $request->request->all('institution');
        
        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('institution', $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return;
        }
        
        try {
            $institution = new Institution();
            $institution->setNom($institutionData['nom'] ?? '');
            $institution->setDescription($institutionData['description'] ?? null);
            
            $typeId = $institutionData['typeInstitution'] ?? $institutionData['type_institution'] ?? null;
            if (!empty($typeId)) {
                $type = $typeInstitutionRepository->find($typeId);
                if ($type) {
                    $institution->setTypeInstitution($type);
                }
            }
            
            $entityManager->persist($institution);
            $entityManager->flush();
            
            $this->addFlash('success', 'Institution ajoutée avec succès!');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'ajout de l\'institution: ' . $e->getMessage());
        }
    }
    
    private function handleTypeForm(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): void {
        $typeData = $request->request->all('type_institution');
        
        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('type_institution', $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return;
        }
        
        try {
            $typeInstitution = new TypeInstitution();
            $typeInstitution->setNom($typeData['nom'] ?? '');
            
            // Génération automatique du slug si non fourni
            $slug = $typeData['slug'] ?? '';
            if (empty($slug)) {
                $slug = $slugger->slug($typeData['nom'])->lower();
            }
            $typeInstitution->setSlug($slug);
            
            $entityManager->persist($typeInstitution);
            $entityManager->flush();
            
            $this->addFlash('success', 'Type d\'institution ajouté avec succès!');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'ajout du type: ' . $e->getMessage());
        }
    }

    #[Route('/new', name: 'app_institution_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $institution = new Institution();
        $form = $this->createForm(InstitutionType::class, $institution);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($institution);
                $entityManager->flush();
                
                $this->addFlash('success', 'Institution créée avec succès!');
                return $this->redirectToRoute('app_institution_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la création: ' . $e->getMessage());
            }
        }

        return $this->render('institution/new.html.twig', [
            'institution' => $institution,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_institution_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Institution $institution,
        EntityManagerInterface $entityManager,
        StructureRepository $structureRepository,
        ServiceRepository $serviceRepository,
        EmployeRepository $employeRepository,
        OrganisationHistoryRepository $historyRepository,
        OrganisationService $organisationService
    ): Response {
        $this->denyAccessUnlessGranted('INSTITUTION_VIEW', $institution);

        $overview = $organisationService->getOrganisationOverview($institution);
        $structures = $structureRepository->findBy(['institution' => $institution], ['nom' => 'ASC']);
        $services = $serviceRepository->findBy(['institution' => $institution], ['nom' => 'ASC']);
        $employes = $employeRepository->findBy(['institution' => $institution], ['nom' => 'ASC'], 25);
        $history = $historyRepository->findByInstitution($institution, 20);

        return $this->render('institution/show.html.twig', [
            'institution' => $institution,
            'overview' => $overview,
            'structures' => $structures,
            'services' => $services,
            'employes' => $employes,
            'history' => $history,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_institution_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Institution $institution, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('INSTITUTION_EDIT', $institution);

        $form = $this->createForm(InstitutionType::class, $institution);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                
                $this->addFlash('success', 'Institution modifiée avec succès!');
                return $this->redirectToRoute('app_institution_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la modification: ' . $e->getMessage());
            }
        }

        return $this->render('institution/edit.html.twig', [
            'institution' => $institution,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_institution_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Institution $institution, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('INSTITUTION_DELETE', $institution);

        if ($this->isCsrfTokenValid('delete'.$institution->getId(), $request->request->get('_token'))) {
            try {
                // Vérification des dépendances avec requêtes optimisées
                $dependencies = $entityManager->createQuery('
                    SELECT COUNT(DISTINCT e.id) as employes,
                           COUNT(DISTINCT s.id) as services,
                           COUNT(DISTINCT u.id) as users
                    FROM App\Entity\Institution i
                    LEFT JOIN i.employes e
                    LEFT JOIN i.services s
                    LEFT JOIN i.users u
                    WHERE i.id = :id
                ')
                ->setParameter('id', $institution->getId())
                ->getSingleResult();
                
                if ($dependencies['employes'] > 0) {
                    $this->addFlash('danger', 'Impossible de supprimer cette institution car elle contient des employés.');
                } elseif ($dependencies['services'] > 0) {
                    $this->addFlash('danger', 'Impossible de supprimer cette institution car elle contient des services.');
                } elseif ($dependencies['users'] > 0) {
                    $this->addFlash('danger', 'Impossible de supprimer cette institution car elle contient des utilisateurs.');
                } else {
                    $entityManager->remove($institution);
                    $entityManager->flush();
                    $this->addFlash('success', 'Institution supprimée avec succès!');
                }
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la suppression: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_institution_index', [], Response::HTTP_SEE_OTHER);
    }
}