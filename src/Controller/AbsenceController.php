<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Entity\Employe;
use App\Entity\TypeAbsence;
use App\Form\AbsenceType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\AbsenceRepository;
use App\Repository\EmployeRepository;
use App\Repository\TypeAbsenceRepository;
use App\Security\Voter\AbsenceVoter;
use App\Service\AbsenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/admin/absences')]
class AbsenceController extends AbstractController
{
    public function __construct(
        private readonly AbsenceService $absenceService,
        private readonly TenantContextInterface $tenantContext
    ) {
    }

    #[Route('/', name: 'app_absence_index', methods: ['GET'])]
    public function index(
        AbsenceRepository $absenceRepository,
        EmployeRepository $employeRepository,
        TypeAbsenceRepository $typeAbsenceRepository,
        Request $request
    ): Response {
        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution active.');

            return $this->redirectToRoute('app_dashboard');
        }

        $statutParam = $request->query->get('status');
        $statut = ($statutParam !== null && $statutParam !== '' && $statutParam !== 'all') ? (int) $statutParam : null;

        $typeId = $request->query->getInt('type');
        $typeAbsence = $typeId > 0 ? $typeAbsenceRepository->find($typeId) : null;

        $employeId = $request->query->getInt('employee');
        $employe = $employeId > 0 ? $employeRepository->find($employeId) : null;

        $search = $request->query->get('search');

        $absences = $absenceRepository->findByFilters(
            $institution,
            $statut,
            $typeAbsence,
            $employe,
            $search
        );

        $employees = $employeRepository->findBy(
            ['institution' => $institution, 'actif' => true],
            ['nom' => 'ASC', 'prenom' => 'ASC']
        );

        $typesAbsences = $typeAbsenceRepository->findAllActive($institution);
        $stats = $absenceRepository->getStatistiques($institution);

        return $this->render('absence/index.html.twig', [
            'absences' => $absences,
            'employees' => $employees,
            'types_absences' => $typesAbsences,
            'stats' => $stats,
            'current_status' => $statutParam,
            'current_type' => $typeId,
            'current_employee' => $employeId,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_absence_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EmployeRepository $employeRepository
    ): Response {
        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution active.');

            return $this->redirectToRoute('app_absence_index');
        }

        $absence = new Absence();
        $absence->setInstitution($institution);

        // Pré-remplissage éventuel de l'agent
        if ($employeId = $request->query->getInt('employe_id')) {
            $employe = $employeRepository->find($employeId);
            if ($employe && $employe->getInstitution() && $employe->getInstitution()->getId() === $institution->getId()) {
                $absence->setEmploye($employe);
            }
        }

        $form = $this->createForm(AbsenceType::class, $absence, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->absenceService->demanderAbsence(
                    employe: $absence->getEmploye(),
                    typeAbsence: $absence->getTypeAbsence(),
                    dateDebut: $absence->getDateDebut(),
                    dateFin: $absence->getDateFin(),
                    motif: $absence->getMotif(),
                    duree: $absence->getDuree() ? (float) $absence->getDuree() : null,
                    unite: $absence->getUnite(),
                    heureDebut: $absence->getHeureDebut(),
                    heureFin: $absence->getHeureFin(),
                    document: $absence->getDocument(),
                    demandeur: $this->getUser()
                );

                $this->addFlash('success', 'La demande d\'autorisation d\'absence a été enregistrée avec succès.');

                return $this->redirectToRoute('app_absence_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('absence/new.html.twig', [
            'form' => $form->createView(),
            'absence' => $absence,
        ]);
    }

    #[Route('/{id}', name: 'app_absence_show', methods: ['GET'])]
    public function show(Absence $absence): Response
    {
        $this->denyAccessUnlessGranted(AbsenceVoter::VIEW, $absence);

        return $this->render('absence/show.html.twig', [
            'absence' => $absence,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_absence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Absence $absence): Response
    {
        $this->denyAccessUnlessGranted(AbsenceVoter::EDIT, $absence);

        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        $form = $this->createForm(AbsenceType::class, $absence, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->absenceService->modifierAbsence(
                    absence: $absence,
                    typeAbsence: $absence->getTypeAbsence(),
                    dateDebut: $absence->getDateDebut(),
                    dateFin: $absence->getDateFin(),
                    motif: $absence->getMotif(),
                    duree: $absence->getDuree() ? (float) $absence->getDuree() : null,
                    unite: $absence->getUnite(),
                    heureDebut: $absence->getHeureDebut(),
                    heureFin: $absence->getHeureFin(),
                    document: $absence->getDocument(),
                    modificateur: $this->getUser()
                );

                $this->addFlash('success', 'La demande d\'autorisation d\'absence a été mise à jour.');

                return $this->redirectToRoute('app_absence_show', ['id' => $absence->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('absence/edit.html.twig', [
            'form' => $form->createView(),
            'absence' => $absence,
        ]);
    }

    #[Route('/{id}/approuver', name: 'app_absence_approuver', methods: ['POST'])]
    public function approuver(Request $request, Absence $absence): Response
    {
        $this->denyAccessUnlessGranted(AbsenceVoter::APPROVE, $absence);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('absence_approuver_' . $absence->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_absence_show', ['id' => $absence->getId()]);
        }

        try {
            $genererActe = $request->request->getBoolean('generer_acte', true);
            $numeroActe = $request->request->get('numero_acte');
            $commentaire = $request->request->get('commentaire');

            $this->absenceService->approuverAbsence(
                absence: $absence,
                approbateur: $this->getUser(),
                genererActe: $genererActe,
                numeroActe: $numeroActe,
                commentaire: $commentaire
            );

            $this->addFlash('success', 'L\'autorisation d\'absence a été accordée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_absence_show', ['id' => $absence->getId()]);
    }

    #[Route('/{id}/rejeter', name: 'app_absence_rejeter', methods: ['POST'])]
    public function rejeter(Request $request, Absence $absence): Response
    {
        $this->denyAccessUnlessGranted(AbsenceVoter::REJECT, $absence);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('absence_rejeter_' . $absence->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_absence_show', ['id' => $absence->getId()]);
        }

        $motifRefus = $request->request->get('motif_refus');
        if (!$motifRefus || trim($motifRefus) === '') {
            $this->addFlash('error', 'Le motif du refus est obligatoire.');

            return $this->redirectToRoute('app_absence_show', ['id' => $absence->getId()]);
        }

        try {
            $this->absenceService->rejeterAbsence(
                absence: $absence,
                refuseur: $this->getUser(),
                motifRefus: trim($motifRefus)
            );

            $this->addFlash('success', 'La demande d\'autorisation d\'absence a été refusée.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_absence_show', ['id' => $absence->getId()]);
    }

    #[Route('/{id}/annuler', name: 'app_absence_annuler', methods: ['POST'])]
    public function annuler(Request $request, Absence $absence): Response
    {
        $this->denyAccessUnlessGranted(AbsenceVoter::CANCEL, $absence);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('absence_annuler_' . $absence->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_absence_show', ['id' => $absence->getId()]);
        }

        try {
            $motif = $request->request->get('motif_annulation');
            $this->absenceService->annulerAbsence($absence, $this->getUser(), $motif);

            $this->addFlash('success', 'L\'autorisation d\'absence a été annulée.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_absence_show', ['id' => $absence->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_absence_delete', methods: ['POST'])]
    public function delete(Request $request, Absence $absence): Response
    {
        $this->denyAccessUnlessGranted(AbsenceVoter::DELETE, $absence);

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('absence_delete_' . $absence->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_absence_index');
        }

        try {
            $this->absenceService->supprimerAbsence($absence, $this->getUser());
            $this->addFlash('success', 'La demande d\'absence a été supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_absence_index');
    }
}
