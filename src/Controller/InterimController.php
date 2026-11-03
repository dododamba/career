<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\Interim;
use App\Entity\User;
use App\Form\InterimType;
use App\MultiTenancy\TenantContext;
use App\Repository\EmployeRepository;
use App\Repository\InterimRepository;
use App\Security\Voter\InterimVoter;
use App\Service\InterimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/interims')]
#[IsGranted('ROLE_USER')]
class InterimController extends AbstractController
{
    public function __construct(
        private InterimService $interimService,
        private TenantContext $tenantContext
    ) {
    }

    #[Route('/', name: 'app_interim_index', methods: ['GET'])]
    public function index(
        Request $request,
        InterimRepository $interimRepository,
        EmployeRepository $employeRepository
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

        $typeDelegation = $request->query->get('type');
        $search = $request->query->get('search');

        $interims = $interimRepository->findByFilters(
            $institution,
            $statut,
            $agent,
            $typeDelegation,
            $search,
            $limit,
            $offset
        );

        $total = $interimRepository->countByFilters($institution, $statut, $agent, $typeDelegation, $search);
        $pagesCount = (int) ceil($total / $limit);

        $employees = $employeRepository->findBy(
            ['institution' => $institution, 'actif' => true],
            ['nom' => 'ASC', 'prenom' => 'ASC']
        );

        $stats = $interimRepository->getStatistiques($institution);

        return $this->render('interim/index.html.twig', [
            'interims' => $interims,
            'employees' => $employees,
            'stats' => $stats,
            'current_status' => $statutParam,
            'current_agent' => $agentId,
            'current_type' => $typeDelegation,
            'search' => $search,
            'page' => $page,
            'pages_count' => $pagesCount,
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'app_interim_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EmployeRepository $employeRepository
    ): Response {
        $institution = $this->tenantContext->getCurrentTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution active.');
            return $this->redirectToRoute('app_interim_index');
        }

        $interim = new Interim();
        $interim->setInstitution($institution);

        // Pré-remplissage éventuel du titulaire
        if ($titulaireId = ($request->query->getInt('titulaire_id') ?: $request->query->getInt('employe_id'))) {
            $titulaire = $employeRepository->find($titulaireId);
            if ($titulaire && $titulaire->getInstitution() && $titulaire->getInstitution()->getId() === $institution->getId()) {
                $interim->setTitulaire($titulaire);
                $interim->setPoste($titulaire->getPosteActuel() ?? $titulaire->getOccupationPosteActive()?->getPoste());
                $interim->setStructure($titulaire->getStructure());
            }
        }

        // Pré-remplissage éventuel de l'intérimaire
        if ($interimaireId = $request->query->getInt('interimaire_id')) {
            $interimaire = $employeRepository->find($interimaireId);
            if ($interimaire && $interimaire->getInstitution() && $interimaire->getInstitution()->getId() === $institution->getId()) {
                $interim->setInterimaire($interimaire);
            }
        }

        $form = $this->createForm(InterimType::class, $interim, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $soumettre = $request->request->has('save_and_submit');

                $createdInterim = $this->interimService->creerInterim(
                    institution: $institution,
                    titulaire: $interim->getTitulaire(),
                    interimaire: $interim->getInterimaire(),
                    dateDebut: $interim->getDateDebut(),
                    dateFin: $interim->getDateFin(),
                    motif: $interim->getMotif(),
                    typeDelegation: $interim->getTypeDelegation(),
                    perimetre: $interim->getPerimetre() ?? [Interim::POUVOIR_TOUS],
                    poste: $interim->getPoste(),
                    structure: $interim->getStructure(),
                    soumettreDirectement: $soumettre,
                    demandeur: $this->getUser() instanceof User ? $this->getUser() : null
                );

                $msg = $soumettre
                    ? sprintf('L\'intérim %s a été créé et soumis pour validation.', $createdInterim->getReference())
                    : sprintf('Le brouillon d\'intérim %s a été enregistré.', $createdInterim->getReference());

                $this->addFlash('success', $msg);

                return $this->redirectToRoute('app_interim_show', ['id' => $createdInterim->getId()]);
            } catch (\LogicException | AccessDeniedException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Erreur lors de la création de l\'intérim : ' . $e->getMessage());
            }
        }

        return $this->render('interim/new.html.twig', [
            'interim' => $interim,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_interim_show', methods: ['GET'])]
    public function show(Interim $interim): Response
    {
        $this->denyAccessUnlessGranted(InterimVoter::VIEW, $interim);

        return $this->render('interim/show.html.twig', [
            'interim' => $interim,
            'logs' => $interim->getInterimLogs(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_interim_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Interim $interim): Response
    {
        $this->denyAccessUnlessGranted(InterimVoter::EDIT, $interim);

        $form = $this->createForm(InterimType::class, $interim, [
            'institution' => $interim->getInstitution(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->interimService->modifierInterim(
                    interim: $interim,
                    titulaire: $interim->getTitulaire(),
                    interimaire: $interim->getInterimaire(),
                    dateDebut: $interim->getDateDebut(),
                    dateFin: $interim->getDateFin(),
                    motif: $interim->getMotif(),
                    typeDelegation: $interim->getTypeDelegation(),
                    perimetre: $interim->getPerimetre() ?? [Interim::POUVOIR_TOUS],
                    poste: $interim->getPoste(),
                    structure: $interim->getStructure(),
                    modificateur: $this->getUser() instanceof User ? $this->getUser() : null
                );

                $this->addFlash('success', sprintf('L\'intérim %s a été mis à jour.', $interim->getReference()));

                return $this->redirectToRoute('app_interim_show', ['id' => $interim->getId()]);
            } catch (\LogicException | AccessDeniedException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('interim/edit.html.twig', [
            'interim' => $interim,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/submit', name: 'app_interim_submit', methods: ['POST'])]
    public function submit(Request $request, Interim $interim): Response
    {
        $this->denyAccessUnlessGranted(InterimVoter::SUBMIT, $interim);

        if ($this->isCsrfTokenValid('submit_interim_' . $interim->getId(), $request->request->get('_token'))) {
            try {
                $this->interimService->soumettreInterim($interim, $this->getUser() instanceof User ? $this->getUser() : null);
                $this->addFlash('success', sprintf('L\'intérim %s a été soumis pour validation.', $interim->getReference()));
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_interim_show', ['id' => $interim->getId()]);
    }

    #[Route('/{id}/validate', name: 'app_interim_validate', methods: ['POST'])]
    public function validate(Request $request, Interim $interim): Response
    {
        $this->denyAccessUnlessGranted(InterimVoter::VALIDATE, $interim);

        if ($this->isCsrfTokenValid('validate_interim_' . $interim->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            try {
                $commentaire = $request->request->get('commentaire');
                $genererActe = $request->request->getBoolean('generer_acte', true);

                $this->interimService->validerInterim(
                    interim: $interim,
                    validateur: $user,
                    genererDecision: $genererActe,
                    commentaire: $commentaire
                );

                $this->addFlash('success', sprintf('L\'intérim %s a été validé et activé avec succès.', $interim->getReference()));
            } catch (\LogicException | AccessDeniedException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Erreur lors de la validation : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_interim_show', ['id' => $interim->getId()]);
    }

    #[Route('/{id}/reject', name: 'app_interim_reject', methods: ['POST'])]
    public function reject(Request $request, Interim $interim): Response
    {
        $this->denyAccessUnlessGranted(InterimVoter::REJECT, $interim);

        if ($this->isCsrfTokenValid('reject_interim_' . $interim->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $motif = $request->request->get('motif_rejet');
            if (empty(trim($motif ?? ''))) {
                $this->addFlash('error', 'Le motif du refus est obligatoire.');
                return $this->redirectToRoute('app_interim_show', ['id' => $interim->getId()]);
            }

            try {
                $this->interimService->refuserInterim($interim, $user, $motif);
                $this->addFlash('warning', sprintf('L\'intérim %s a été refusé.', $interim->getReference()));
            } catch (\LogicException | AccessDeniedException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_interim_show', ['id' => $interim->getId()]);
    }

    #[Route('/{id}/revoke', name: 'app_interim_revoke', methods: ['POST'])]
    public function revoke(Request $request, Interim $interim): Response
    {
        $this->denyAccessUnlessGranted(InterimVoter::REVOKE, $interim);

        if ($this->isCsrfTokenValid('revoke_interim_' . $interim->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $motif = $request->request->get('motif_revocation');
            if (empty(trim($motif ?? ''))) {
                $this->addFlash('error', 'Le motif de la révocation est obligatoire.');
                return $this->redirectToRoute('app_interim_show', ['id' => $interim->getId()]);
            }

            try {
                $this->interimService->revoquerInterim($interim, $user, $motif);
                $this->addFlash('warning', sprintf('L\'intérim %s a été révoqué avec succès.', $interim->getReference()));
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_interim_show', ['id' => $interim->getId()]);
    }

    #[Route('/{id}/close', name: 'app_interim_close', methods: ['POST'])]
    public function close(Request $request, Interim $interim): Response
    {
        $this->denyAccessUnlessGranted(InterimVoter::CLOSE, $interim);

        if ($this->isCsrfTokenValid('close_interim_' . $interim->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $motif = $request->request->get('motif_cloture');

            try {
                $this->interimService->cloturerInterim($interim, $user, $motif);
                $this->addFlash('success', sprintf('L\'intérim %s a été clôturé.', $interim->getReference()));
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_interim_show', ['id' => $interim->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_interim_delete', methods: ['POST'])]
    public function delete(Request $request, Interim $interim): Response
    {
        $this->denyAccessUnlessGranted(InterimVoter::DELETE, $interim);

        if ($this->isCsrfTokenValid('delete_interim_' . $interim->getId(), $request->request->get('_token'))) {
            try {
                $this->interimService->supprimerInterim($interim, $this->getUser() instanceof User ? $this->getUser() : null);
                $this->addFlash('success', 'L\'intérim a été supprimé.');
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_interim_index');
    }
}
