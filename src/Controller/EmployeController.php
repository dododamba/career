<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Form\EmployeType;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/employe')]
#[IsGranted('ROLE_USER')]
final class EmployeController extends AbstractController
{
    #[Route(name: 'app_employe_index', methods: ['GET'])]
    public function index(EmployeRepository $employeRepository): Response
    {
        return $this->render('employe/index.html.twig', [
            'employes' => $employeRepository->findAll(),
        ]);
    }

    #[Route('/{id}/solde', name: 'app_employe_solde', methods: ['GET'])]
    public function getSolde(Employe $employe, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('EMPLOYE_VIEW', $employe);

        $data = [
            'id' => $employe->getId(),
            'nom' => $employe->getNom(),
            'prenom' => $employe->getPrenom(),
            'soldeConges' => $employe->getSoldesConges()->count(),
        ];
        
        if ($employe->getService()) {
            $data['service'] = $employe->getService()->getNom();
        }
        
        return new JsonResponse($data);
    }

    #[Route('/search', name: 'app_employe_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $term = $request->query->get('term', '');
        
        $qb = $entityManager->createQueryBuilder();
        $qb->select('e')
           ->from('App:Employe', 'e')
           ->where('LOWER(e.nom) LIKE LOWER(:term)')
           ->orWhere('LOWER(e.prenom) LIKE LOWER(:term)')
           ->setParameter('term', '%' . $term . '%')
           ->setMaxResults(15)
           ->orderBy('e.nom', 'ASC');
        
        $result = $qb->getQuery()->getResult();
        
        $data = [];
        foreach ($result as $employe) {
            $data[] = [
                'id' => $employe->getId(),
                'text' => $employe->getNom() . ' ' . $employe->getPrenom()
            ];
        }
        
        return new JsonResponse(['results' => $data]);
    }

    #[Route('/export', name: 'app_employe_export', methods: ['GET'])]
    public function export(EmployeRepository $employeRepository): Response
    {
        $this->denyAccessUnlessGranted('EMPLOYE_EXPORT');

        return $this->render('employe/index.html.twig', [
            'employes' => $employeRepository->findAll(),
        ]);
    }

    #[Route('/presence', name: 'app_employe_presence', methods: ['GET'])]
    public function empPresence(EmployeRepository $employeRepository): Response
    {
        return $this->render('employe/index.html.twig', [
            'employes' => $employeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_employe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EMPLOYE_CREATE');

        // Récupération de l'utilisateur connecté
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un employé.');
            return $this->redirectToRoute('app_login');
        }

        // Vérification que l'utilisateur a une institution
        $institution = $currentUser->getInstitution();
        if (!$institution) {
            $this->addFlash('error', 'Votre compte n\'est associé à aucune institution. Contactez l\'administrateur.');
            return $this->redirectToRoute('app_employe_index');
        }

        $employe = new Employe();
        
        // Création du formulaire avec l'institution en option pour filtrer les services
        $form = $this->createForm(EmployeType::class, $employe, [
            'institution' => $institution
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Association automatique de l'utilisateur connecté et de l'institution à l'employé
                $employe->setUser($currentUser);
                $employe->setInstitution($institution);
                
                // Génération automatique du matricule si non fourni
                if (!$employe->getMatricule()) {
                    $employe->setMatricule($this->generateMatricule($entityManager, $institution));
                }
                
                // Vérification de l'unicité du matricule dans l'institution
                $existingEmploye = $entityManager->getRepository(Employe::class)
                    ->findOneBy([
                        'matricule' => $employe->getMatricule(),
                        'user' => $currentUser // Recherche dans la même institution via user
                    ]);
                
                if ($existingEmploye) {
                    $this->addFlash('error', 'Un employé avec ce matricule existe déjà dans votre institution.');
                    return $this->render('employe/new.html.twig', [
                        'employe' => $employe,
                        'form' => $form->createView(),
                        'institution' => $institution,
                    ]);
                }

                // Activation par défaut
                if ($employe->isActif() === null) {
                    $employe->setActif(true);
                }

                $entityManager->persist($employe);
                $entityManager->flush();

                $this->addFlash('success', sprintf(
                    'L\'employé %s a été créé avec succès dans l\'institution %s.',
                    $employe->getNomComplet(),
                    $institution->getNom()
                ));

                return $this->redirectToRoute('app_employe_index');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création de l\'employé : ' . $e->getMessage());
            }
        }

        return $this->render('employe/new.html.twig', [
            'employe' => $employe,
            'form' => $form->createView(),
            'institution' => $institution,
        ]);
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

    #[Route('/{id}', name: 'app_employe_show', methods: ['GET'])]
    public function show(Employe $employe, \App\Service\AgentService $agentService): Response
    {
        $this->denyAccessUnlessGranted('EMPLOYE_VIEW', $employe);

        $viewData = $agentService->getAgent360View($employe);

        return $this->render('employe/show.html.twig', $viewData);
    }

    #[Route('/{id}/edit', name: 'app_employe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Employe $employe, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EMPLOYE_EDIT', $employe);

        $form = $this->createForm(EmployeType::class, $employe, [
            'institution' => $employe->getInstitution(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_employe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employe/edit.html.twig', [
            'employe' => $employe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employe_delete', methods: ['POST'])]
    public function delete(Request $request, Employe $employe, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EMPLOYE_DELETE', $employe);

        if ($this->isCsrfTokenValid('delete'.$employe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($employe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_employe_index', [], Response::HTTP_SEE_OTHER);
    }
}
