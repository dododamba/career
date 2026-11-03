<?php

namespace App\Controller;

use App\Entity\Visite;
use App\Form\VisiteType;
use App\Repository\VisiteRepository;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/visite')]
final class VisiteController extends AbstractController
{
    #[Route('/', name: 'app_visite_index', methods: ['GET'])]
    public function index(VisiteRepository $visiteRepository, Request $request): Response
    {
        // Récupérer l'utilisateur connecté et son institution
        $user = $this->getUser();
        $institution = $user->getInstitution();
        
        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution pour voir les visites.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Récupérer la date sélectionnée ou aujourd'hui par défaut
        $dateParam = $request->query->get('date');
        $selectedDate = $dateParam ? new \DateTime($dateParam) : new \DateTime('today');
        $formattedDate = $selectedDate->format('Y-m-d');

        // Statistiques pour les cards
        $today = new \DateTime('today');
        
        $visitesAujourdhui = $visiteRepository->countByInstitution($institution);
        if ($selectedDate->format('Y-m-d') === $today->format('Y-m-d')) {
            $visitesActives = $visiteRepository->countVisitesActivesParDateEtInstitution($today, $institution);
            $visitesTerminees = $visiteRepository->countVisitesTermineesParDateEtInstitution($today, $institution);
        } else {
            $visitesActives = $visiteRepository->countVisitesActivesParDateEtInstitution($selectedDate, $institution);
            $visitesTerminees = $visiteRepository->countVisitesTermineesParDateEtInstitution($selectedDate, $institution);
        }
        
        $visitesSemaine = $visiteRepository->countVisitesSemaineEtInstitution($institution);
        $visitesMois = $visiteRepository->countVisitesMoisEtInstitution($institution);

        // Calcul des tendances (comparaison avec la semaine précédente)
        $lastWeek = (clone $today)->modify('-7 days');
        $visitesLastWeek = $visiteRepository->countByInstitution($institution); // À adapter selon vos besoins
        
        $tendanceVisites = $visitesLastWeek > 0 ? round((($visitesAujourdhui - $visitesLastWeek) / $visitesLastWeek) * 100) : 0;

        // Visites pour la date sélectionnée
        $visites = $visiteRepository->findByDateAndInstitution($selectedDate, $institution);

        return $this->render('visite/index.html.twig', [
            'visites' => $visites,
            'date_actuelle' => $formattedDate,
            'stats' => [
                'total_visites' => $visitesAujourdhui,
                'actives' => $visitesActives,
                'terminees' => $visitesTerminees,
                'semaine' => $visitesSemaine,
                'mois' => $visitesMois,
                'tendance_visites' => $tendanceVisites,
            ],
        ]);
    }

    #[Route('/new', name: 'app_visite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté et son institution
        $user = $this->getUser();
        $institution = $user->getInstitution();
        
        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution pour créer une visite.');
            return $this->redirectToRoute('app_visite_index');
        }

        $visite = new Visite();
        $visite->setArrivee(new \DateTime());
        
        $form = $this->createForm(VisiteType::class, $visite, [
            'institution' => $institution
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($visite->getInstitution() === null) {
                $visite->setInstitution($visite->getEmploye()?->getInstitution() ?? $institution);
            }
            $entityManager->persist($visite);
            $entityManager->flush();

            $this->addFlash('success', 'La visite a été enregistrée avec succès.');
            return $this->redirectToRoute('app_visite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('visite/new.html.twig', [
            'visite' => $visite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_visite_show', methods: ['GET'])]
    public function show(Visite $visite): Response
    {
        // Vérifier que la visite appartient à l'institution de l'utilisateur
        if (!$this->canAccessVisite($visite)) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette visite.');
            return $this->redirectToRoute('app_visite_index');
        }

        return $this->render('visite/show.html.twig', [
            'visite' => $visite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_visite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Visite $visite, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que la visite appartient à l'institution de l'utilisateur
        if (!$this->canAccessVisite($visite)) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette visite.');
            return $this->redirectToRoute('app_visite_index');
        }

        $user = $this->getUser();
        $institution = $user->getInstitution();

        $form = $this->createForm(VisiteType::class, $visite, [
            'institution' => $institution
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La visite a été mise à jour avec succès.');
            return $this->redirectToRoute('app_visite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('visite/edit.html.twig', [
            'visite' => $visite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/terminer', name: 'app_visite_terminer', methods: ['POST'])]
    public function terminer(Request $request, Visite $visite, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que la visite appartient à l'institution de l'utilisateur
        if (!$this->canAccessVisite($visite)) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette visite.');
            return $this->redirectToRoute('app_visite_index');
        }

        if ($this->isCsrfTokenValid('terminer'.$visite->getId(), $request->getPayload()->getString('_token'))) {
            $visite->setStatut(true);
            $visite->setDepart(new \DateTime());
            $entityManager->flush();
            
            $this->addFlash('success', 'La visite a été marquée comme terminée.');
        }

        return $this->redirectToRoute('app_visite_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_visite_delete', methods: ['POST'])]
    public function delete(Request $request, Visite $visite, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que la visite appartient à l'institution de l'utilisateur
        if (!$this->canAccessVisite($visite)) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette visite.');
            return $this->redirectToRoute('app_visite_index');
        }

        if ($this->isCsrfTokenValid('delete'.$visite->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($visite);
            $entityManager->flush();
            
            $this->addFlash('success', 'La visite a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_visite_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/employe/{id}/visites', name: 'app_visite_par_employe', methods: ['GET'])]
    public function visitesParEmploye(int $id, EmployeRepository $employeRepository, VisiteRepository $visiteRepository): Response
    {
        $employe = $employeRepository->find($id);
        
        if (!$employe) {
            throw $this->createNotFoundException('Employé non trouvé');
        }

        $this->denyAccessUnlessGranted('EMPLOYE_VIEW', $employe);
        
        $visites = $visiteRepository->findByEmploye($employe);
        
        return $this->render('visite/visites_employe.html.twig', [
            'employe' => $employe,
            'visites' => $visites,
        ]);
    }

    /**
     * Vérifie si l'utilisateur peut accéder à cette visite
     */
    private function canAccessVisite(Visite $visite): bool
    {
        return $this->isGranted('VISITE_VIEW', $visite);
    }
}