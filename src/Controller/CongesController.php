<?php

namespace App\Controller;

use App\Entity\Conges;
use App\Entity\Employe;
use App\Entity\TypeConges;
use App\Form\CongesType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\CongesRepository;
use App\Repository\EmployeRepository;
use App\Repository\SoldeCongeRepository;
use App\Repository\TypeCongesRepository;
use App\Service\CongeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/admin/conges')]
class CongesController extends AbstractController
{
    public function __construct(
        private readonly CongeService $congeService,
        private readonly TenantContextInterface $tenantContext
    ) {
    }

    #[Route('/', name: 'app_conges_index', methods: ['GET'])]
    public function index(
        CongesRepository $congesRepository,
        EmployeRepository $employeRepository,
        TypeCongesRepository $typeCongesRepository,
        Request $request
    ): Response {
        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution active.');
            return $this->redirectToRoute('app_dashboard');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Filtres optionnels
        $filters = [];
        $statusParam = $request->query->get('status');
        if ($statusParam !== null && $statusParam !== '' && $statusParam !== 'all') {
            $filters['statut'] = (int) $statusParam;
        }

        $employeeParam = $request->query->get('employee');
        if ($employeeParam && $employeeParam !== 'all') {
            $filters['employe'] = (int) $employeeParam;
        }

        $typeParam = $request->query->get('type');
        if ($typeParam && $typeParam !== 'all') {
            $filters['typeConge'] = (int) $typeParam;
        }

        if ($dateDebut = $request->query->get('date_debut')) {
            $filters['dateDebut'] = $dateDebut;
        }

        if ($dateFin = $request->query->get('date_fin')) {
            $filters['dateFin'] = $dateFin;
        }

        // Requête filtrée
        $conges = $congesRepository->findByFilters($filters, $limit, $offset);
        $total = $congesRepository->countByFilters($filters);
        $pages_count = (int) ceil($total / $limit);

        // Employés de l'institution pour le filtre
        $employees = $employeRepository->findBy(
            ['institution' => $institution, 'actif' => true],
            ['nom' => 'ASC', 'prenom' => 'ASC']
        );

        $typesConges = $typeCongesRepository->findAllActive();

        // Statistiques globales de l'institution
        $statsData = $this->congeService->getStatistiquesInstitution($institution);
        $stats = [
            'total' => $statsData['total'],
            'pending' => $statsData['en_attente'],
            'approved' => $statsData['approuves'],
            'rejected' => $statsData['refuses'],
            'cancelled' => $statsData['annules'],
            'total_jours' => $statsData['total_jours_pris'],
        ];

        return $this->render('conges/index.html.twig', [
            'conges' => $conges,
            'page' => $page,
            'pages_count' => $pages_count,
            'total' => $total,
            'stats' => $stats,
            'employees' => $employees,
            'typesConges' => $typesConges,
            'currentFilters' => [
                'status' => $statusParam ?? 'all',
                'employee' => $employeeParam ?? 'all',
                'type' => $typeParam ?? 'all',
            ],
        ]);
    }

    #[Route('/new', name: 'app_conges_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EmployeRepository $employeRepository,
        TypeCongesRepository $typeCongesRepository,
        SoldeCongeRepository $soldeCongeRepository
    ): Response {
        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution active.');
            return $this->redirectToRoute('app_dashboard');
        }

        $conge = new Conges();
        $currentUser = $this->getUser();

        // Pré-remplir l'employé s'il s'agit de l'utilisateur connecté
        if ($currentUser) {
            $employe = $employeRepository->findOneBy(['user' => $currentUser]);
            if ($employe) {
                $conge->setEmploye($employe);
            }
        }

        $form = $this->createForm(CongesType::class, $conge, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $congeCreated = $this->congeService->demanderConge(
                    employe: $conge->getEmploye(),
                    typeConge: $conge->getTypeConge(),
                    dateDebut: $conge->getDateDebut(),
                    dateFin: $conge->getDateFin(),
                    motif: $conge->getMotif(),
                    document: $conge->getDocument(),
                    demandeur: $currentUser
                );

                $this->addFlash('success', 'Votre demande de congé a été enregistrée avec succès.');
                return $this->redirectToRoute('app_conges_show', ['id' => $congeCreated->getId()]);
            } catch (\InvalidArgumentException|\LogicException|AccessDeniedException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $selectedEmploye = $conge->getEmploye();
        $typesConges = $typeCongesRepository->findAllActive();

        $soldes = [];
        $soldeTotal = 0.0;
        if ($selectedEmploye) {
            $soldesConges = $this->congeService->getSoldesAgent($selectedEmploye);
            foreach ($soldesConges as $s) {
                $val = (float) $s->getSolde();
                $soldes[$s->getTypeConge()->getId()] = $val;
                if ($s->getTypeConge()->isDeductible()) {
                    $soldeTotal += $val;
                }
            }
        }

        return $this->render('conges/new.html.twig', [
            'conge' => $conge,
            'form' => $form->createView(),
            'employe' => $selectedEmploye,
            'typesConges' => $typesConges,
            'soldes' => $soldes,
            'soldeTotal' => $soldeTotal,
        ]);
    }

    #[Route('/{id}', name: 'app_conges_show', methods: ['GET'])]
    public function show(Conges $conge, SoldeCongeRepository $soldeCongeRepository): Response
    {
        $this->denyAccessUnlessGranted('CONGES_VIEW', $conge);

        $employe = $conge->getEmploye();
        $soldes = $employe ? $this->congeService->getSoldesAgent($employe, (int) $conge->getDateDebut()->format('Y')) : [];

        return $this->render('conges/show.html.twig', [
            'conge' => $conge,
            'soldes' => $soldes,
            'date' => new \DateTime(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_conges_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Conges $conge,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('CONGES_EDIT', $conge);

        $institution = $conge->getInstitution() ?? $this->getUser()?->getInstitution();

        $form = $this->createForm(CongesType::class, $conge, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($conge->getDateFin() < $conge->getDateDebut()) {
                $this->addFlash('error', 'La date de fin ne peut pas être antérieure à la date de début.');
                return $this->render('conges/edit.html.twig', [
                    'conge' => $conge,
                    'form' => $form->createView(),
                ]);
            }

            // Vérifier anti-chevauchement en excluant ce congé
            if ($this->congeService->verifierChevauchement($conge->getEmploye(), $conge->getDateDebut(), $conge->getDateFin(), $conge->getId())) {
                $this->addFlash('error', 'L\'agent a déjà une autre demande de congé ou absence validée ou en attente sur cette période.');
                return $this->render('conges/edit.html.twig', [
                    'conge' => $conge,
                    'form' => $form->createView(),
                ]);
            }

            $jours = $this->congeService->calculerDureeCalendaire($conge->getDateDebut(), $conge->getDateFin());
            $conge->setDateDiff($jours);
            $conge->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Demande de congé modifiée avec succès.');
            return $this->redirectToRoute('app_conges_show', ['id' => $conge->getId()]);
        }

        return $this->render('conges/edit.html.twig', [
            'conge' => $conge,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/approve', name: 'app_conges_approve', methods: ['POST'])]
    public function approve(Request $request, Conges $conge): Response
    {
        $this->denyAccessUnlessGranted('CONGES_APPROVE', $conge);

        if (!$this->isCsrfTokenValid('approve' . $conge->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_conges_show', ['id' => $conge->getId()]);
        }

        try {
            $genererActe = $request->request->getBoolean('generer_acte', true);
            $this->congeService->approuverConge($conge, $this->getUser(), $genererActe);
            $this->addFlash('success', 'La demande de congé a été approuvée avec succès.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\'approbation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_conges_show', ['id' => $conge->getId()]);
    }

    #[Route('/{id}/reject', name: 'app_conges_reject', methods: ['POST'])]
    public function reject(Request $request, Conges $conge): Response
    {
        $this->denyAccessUnlessGranted('CONGES_REJECT', $conge);

        if (!$this->isCsrfTokenValid('reject' . $conge->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_conges_show', ['id' => $conge->getId()]);
        }

        $motifRefus = trim((string) $request->request->get('motif_refus', ''));
        if ($motifRefus === '') {
            $motifRefus = 'Motif non spécifié';
        }

        try {
            $this->congeService->rejeterConge($conge, $this->getUser(), $motifRefus);
            $this->addFlash('success', 'La demande de congé a été rejetée.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors du rejet : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_conges_show', ['id' => $conge->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_conges_cancel', methods: ['POST'])]
    public function cancel(Request $request, Conges $conge): Response
    {
        $this->denyAccessUnlessGranted('CONGES_CANCEL', $conge);

        if (!$this->isCsrfTokenValid('cancel' . $conge->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_conges_show', ['id' => $conge->getId()]);
        }

        $motif = trim((string) $request->request->get('motif_annulation', 'Annulation demandée'));

        try {
            $this->congeService->annulerConge($conge, $this->getUser(), $motif);
            $this->addFlash('success', 'Le congé a été annulé et les jours ont été recrédités le cas échéant.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\'annulation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_conges_show', ['id' => $conge->getId()]);
    }

    #[Route('/{id}', name: 'app_conges_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Conges $conge,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('CONGES_DELETE', $conge);

        if ($this->isCsrfTokenValid('delete' . $conge->getId(), $request->request->get('_token'))) {
            $entityManager->remove($conge);
            $entityManager->flush();

            $this->addFlash('success', 'Demande de congé supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_conges_index', [], Response::HTTP_SEE_OTHER);
    }
}