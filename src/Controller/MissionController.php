<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\Mission;
use App\Form\MissionRapportType;
use App\Form\MissionType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\EmployeRepository;
use App\Repository\MissionRepository;
use App\Security\Voter\MissionVoter;
use App\Service\MissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/missions')]
class MissionController extends AbstractController
{
    public function __construct(
        private readonly MissionService $missionService,
        private readonly TenantContextInterface $tenantContext
    ) {
    }

    #[Route('/', name: 'app_mission_index', methods: ['GET'])]
    public function index(
        MissionRepository $missionRepository,
        EmployeRepository $employeRepository,
        Request $request
    ): Response {
        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution active.');

            return $this->redirectToRoute('app_dashboard');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $statutParam = $request->query->get('status');
        $statut = ($statutParam !== null && $statutParam !== '' && $statutParam !== 'all') ? (int) $statutParam : null;

        $agentId = $request->query->getInt('agent');
        $agent = $agentId > 0 ? $employeRepository->find($agentId) : null;

        $search = $request->query->get('search');

        $missions = $missionRepository->findByFilters(
            $institution,
            $statut,
            $agent,
            $search,
            $limit,
            $offset
        );

        $total = $missionRepository->countByFilters($institution, $statut, $agent, $search);
        $pagesCount = (int) ceil($total / $limit);

        $employees = $employeRepository->findBy(
            ['institution' => $institution, 'actif' => true],
            ['nom' => 'ASC', 'prenom' => 'ASC']
        );

        $stats = $missionRepository->getStatistiques($institution);

        return $this->render('mission/index.html.twig', [
            'missions' => $missions,
            'employees' => $employees,
            'stats' => $stats,
            'current_status' => $statutParam,
            'current_agent' => $agentId,
            'search' => $search,
            'page' => $page,
            'pages_count' => $pagesCount,
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'app_mission_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EmployeRepository $employeRepository
    ): Response {
        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution active.');

            return $this->redirectToRoute('app_mission_index');
        }

        $mission = new Mission();
        $mission->setInstitution($institution);

        // Pré-remplissage éventuel du chef de mission ou participant
        $paramEmployeId = $request->query->getInt('employe_id') ?: $request->query->getInt('chef_id');
        if ($paramEmployeId) {
            $employe = $employeRepository->find($paramEmployeId);
            if ($employe && $employe->getInstitution() && $employe->getInstitution()->getId() === $institution->getId()) {
                $mission->setChefMission($employe);
            }
        }

        $form = $this->createForm(MissionType::class, $mission, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $soumettre = $request->request->has('save_and_submit');

                // Récupérer les participants sélectionnés
                $selectedAgents = $form->get('agentsParticipants')->getData() ?? [];
                $participantsData = [];
                foreach ($selectedAgents as $agent) {
                    $participantsData[] = [
                        'employe' => $agent,
                        'role' => 'PARTICIPANT',
                    ];
                }

                $createdMission = $this->missionService->creerMission(
                    institution: $institution,
                    objet: $mission->getObjet(),
                    destination: $mission->getDestination(),
                    dateDebut: $mission->getDateDebut(),
                    dateFin: $mission->getDateFin(),
                    description: $mission->getDescription(),
                    pays: $mission->getPays(),
                    lieuPrecis: $mission->getLieuPrecis(),
                    itineraire: $mission->getItineraire(),
                    modeTransport: $mission->getModeTransport(),
                    budgetEstime: $mission->getBudgetEstime(),
                    imputationBudgetaire: $mission->getImputationBudgetaire(),
                    priseEnCharge: $mission->getPriseEnCharge(),
                    chefMission: $mission->getChefMission(),
                    participantsData: $participantsData,
                    soumettreDirectement: $soumettre,
                    demandeur: $this->getUser()
                );

                $msg = $soumettre
                    ? 'La mission a été enregistrée et soumise pour validation avec succès.'
                    : 'Le projet de mission a été enregistré en brouillon.';
                $this->addFlash('success', $msg);

                return $this->redirectToRoute('app_mission_show', ['id' => $createdMission->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('mission/new.html.twig', [
            'form' => $form->createView(),
            'mission' => $mission,
        ]);
    }

    #[Route('/{id}', name: 'app_mission_show', methods: ['GET'])]
    public function show(Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::VIEW, $mission);

        return $this->render('mission/show.html.twig', [
            'mission' => $mission,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mission_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::EDIT, $mission);

        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        $form = $this->createForm(MissionType::class, $mission, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->missionService->modifierMission(
                    mission: $mission,
                    objet: $mission->getObjet(),
                    destination: $mission->getDestination(),
                    dateDebut: $mission->getDateDebut(),
                    dateFin: $mission->getDateFin(),
                    description: $mission->getDescription(),
                    pays: $mission->getPays(),
                    lieuPrecis: $mission->getLieuPrecis(),
                    itineraire: $mission->getItineraire(),
                    modeTransport: $mission->getModeTransport(),
                    budgetEstime: $mission->getBudgetEstime(),
                    imputationBudgetaire: $mission->getImputationBudgetaire(),
                    priseEnCharge: $mission->getPriseEnCharge(),
                    chefMission: $mission->getChefMission(),
                    modificateur: $this->getUser()
                );

                $this->addFlash('success', 'La mission a été mise à jour.');

                return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('mission/edit.html.twig', [
            'form' => $form->createView(),
            'mission' => $mission,
        ]);
    }

    #[Route('/{id}/soumettre', name: 'app_mission_soumettre', methods: ['POST'])]
    public function soumettre(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::SUBMIT, $mission);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('mission_soumettre_' . $mission->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
        }

        try {
            $this->missionService->soumettreMission($mission, $this->getUser());
            $this->addFlash('success', 'La mission a été soumise pour validation.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
    }

    #[Route('/{id}/valider', name: 'app_mission_valider', methods: ['POST'])]
    public function valider(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::VALIDATE, $mission);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('mission_valider_' . $mission->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
        }

        try {
            $genererOrdre = $request->request->getBoolean('generer_ordre', true);
            $numeroOrdre = $request->request->get('numero_ordre');
            $commentaire = $request->request->get('commentaire');

            $this->missionService->validerMission(
                mission: $mission,
                validateur: $this->getUser(),
                genererOrdreMission: $genererOrdre,
                numeroOrdre: $numeroOrdre,
                commentaire: $commentaire
            );

            $this->addFlash('success', 'La mission a été validée avec succès et l\'ordre de mission officiel a été généré.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
    }

    #[Route('/{id}/demarrer', name: 'app_mission_demarrer', methods: ['POST'])]
    public function demarrer(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::START, $mission);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('mission_demarrer_' . $mission->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
        }

        try {
            $this->missionService->demarrerMission($mission, $this->getUser());
            $this->addFlash('success', 'La mission est désormais marquée en cours d\'exécution.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
    }

    #[Route('/{id}/terminer', name: 'app_mission_terminer', methods: ['POST'])]
    public function terminer(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::COMPLETE, $mission);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('mission_terminer_' . $mission->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
        }

        try {
            $this->missionService->terminerMission($mission, $this->getUser());
            $this->addFlash('success', 'La mission est marquée achevée. Le dépôt du rapport de fin de mission est attendu.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
    }

    #[Route('/{id}/rapport', name: 'app_mission_rapport', methods: ['GET', 'POST'])]
    public function rapport(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::REPORT, $mission);

        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        $form = $this->createForm(MissionRapportType::class, $mission, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->missionService->soumettreRapport(
                    mission: $mission,
                    rapportTexte: (string) $mission->getRapportTexte(),
                    documentRapport: $mission->getDocumentRapport(),
                    redacteur: $this->getUser()
                );

                $this->addFlash('success', 'Le rapport de fin de mission a été enregistré avec succès.');

                return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('mission/rapport.html.twig', [
            'form' => $form->createView(),
            'mission' => $mission,
        ]);
    }

    #[Route('/{id}/cloturer', name: 'app_mission_cloturer', methods: ['POST'])]
    public function cloturer(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::CLOSE, $mission);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('mission_cloturer_' . $mission->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
        }

        try {
            $commentaire = $request->request->get('commentaire');
            $this->missionService->validerRapportEtCloturer($mission, $this->getUser(), $commentaire);

            $this->addFlash('success', 'Le rapport a été validé et la mission a été clôturée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
    }

    #[Route('/{id}/rejeter', name: 'app_mission_rejeter', methods: ['POST'])]
    public function rejeter(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::REJECT, $mission);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('mission_rejeter_' . $mission->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
        }

        $motifRefus = $request->request->get('motif_refus');
        if (!$motifRefus || trim($motifRefus) === '') {
            $this->addFlash('error', 'Le motif du refus est obligatoire.');

            return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
        }

        try {
            $this->missionService->rejeterMission($mission, $this->getUser(), trim($motifRefus));
            $this->addFlash('success', 'La mission a été refusée.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
    }

    #[Route('/{id}/annuler', name: 'app_mission_annuler', methods: ['POST'])]
    public function annuler(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::CANCEL, $mission);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('mission_annuler_' . $mission->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
        }

        try {
            $motif = $request->request->get('motif_annulation');
            $this->missionService->annulerMission($mission, $this->getUser(), $motif);

            $this->addFlash('success', 'La mission a été annulée et son ordre de mission abrogé.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mission_show', ['id' => $mission->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_mission_delete', methods: ['POST'])]
    public function delete(Request $request, Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(MissionVoter::DELETE, $mission);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('mission_delete_' . $mission->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_mission_index');
        }

        try {
            $this->missionService->supprimerMission($mission, $this->getUser());
            $this->addFlash('success', 'La mission a été supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mission_index');
    }
}
