<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Employe;
use App\Form\AffectationType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\AffectationRepository;
use App\Repository\EmployeRepository;
use App\Security\Voter\AffectationVoter;
use App\Service\CarriereService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/affectation')]
#[IsGranted('ROLE_USER')]
final class AffectationController extends AbstractController
{
    #[Route(name: 'app_affectation_index', methods: ['GET'])]
    public function index(
        Request $request,
        AffectationRepository $affectationRepository,
        TenantContextInterface $tenantContext
    ): Response {
        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Aucune institution active.');
            return $this->redirectToRoute('app_login');
        }

        $statut = $request->query->get('statut');
        $type = $request->query->get('type');

        $affectations = $affectationRepository->findByInstitution($institution, $statut, $type);
        $stats = $affectationRepository->getStatsByInstitution($institution);

        return $this->render('affectation/index.html.twig', [
            'affectations' => $affectations,
            'stats' => $stats,
            'currentStatut' => $statut,
            'currentType' => $type,
            'institution' => $institution,
        ]);
    }

    #[Route('/nouveau', name: 'app_affectation_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
        TenantContextInterface $tenantContext,
        EmployeRepository $employeRepository,
        CarriereService $carriereService
    ): Response {
        $this->denyAccessUnlessGranted(AffectationVoter::CREATE, Affectation::class);

        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();
        if (!$institution && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Institution introuvable.');
            return $this->redirectToRoute('app_affectation_index');
        }

        $employeId = $request->query->getInt('employe_id');
        $presetEmploye = null;
        if ($employeId > 0) {
            $presetEmploye = $employeRepository->find($employeId);
            // Vérification de sécurité multi-tenant
            if ($presetEmploye && $presetEmploye->getInstitution()?->getId() !== $institution->getId()) {
                throw $this->createAccessDeniedException('Accès refusé à cet employé.');
            }
        }

        $affectation = new Affectation();
        if ($presetEmploye) {
            $affectation->setEmploye($presetEmploye);
        }

        $form = $this->createForm(AffectationType::class, $affectation, [
            'institution' => $institution,
            'is_agent_preset' => ($presetEmploye !== null),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employe = $presetEmploye ?? $form->get('employe')->getData();
            if (!$employe) {
                $this->addFlash('error', 'Veuillez sélectionner un agent.');
                return $this->render('affectation/new.html.twig', [
                    'form' => $form,
                    'presetEmploye' => $presetEmploye,
                ]);
            }

            $activerImmediatement = (bool) $form->get('activer_immediatement')->getData();

            try {
                $carriereService->creerMouvement(
                    employe: $employe,
                    typeMouvement: $affectation->getTypeMouvement(),
                    dateEffet: $affectation->getDateEffet(),
                    structure: $affectation->getStructure(),
                    service: $affectation->getService(),
                    poste: $affectation->getPoste(),
                    fonction: $affectation->getFonction(),
                    dateDecision: $affectation->getDateDecision(),
                    datePriseService: $affectation->getDatePriseService(),
                    referenceActe: $affectation->getReferenceActe(),
                    motif: $affectation->getMotif(),
                    commentaire: $affectation->getCommentaire(),
                    autoValiderEtActiver: $activerImmediatement
                );

                $msg = $activerImmediatement
                    ? sprintf('Mouvement validé et activé avec succès pour %s.', $employe->getNomComplet())
                    : sprintf('Proposition de mouvement enregistrée pour %s (en attente de validation).', $employe->getNomComplet());

                $this->addFlash('success', $msg);

                if ($presetEmploye) {
                    return $this->redirectToRoute('app_employe_show', ['id' => $presetEmploye->getId()]);
                }

                return $this->redirectToRoute('app_affectation_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            }
        }

        return $this->render('affectation/new.html.twig', [
            'form' => $form,
            'presetEmploye' => $presetEmploye,
        ]);
    }

    #[Route('/{id}', name: 'app_affectation_show', methods: ['GET'])]
    public function show(Affectation $affectation, CarriereService $carriereService): Response
    {
        $this->denyAccessUnlessGranted(AffectationVoter::VIEW, $affectation);

        $employe = $affectation->getEmploye();
        $timeline = $employe ? $carriereService->getCarriereTimeline($employe) : [];

        return $this->render('affectation/show.html.twig', [
            'affectation' => $affectation,
            'employe' => $employe,
            'timeline' => $timeline,
        ]);
    }

    #[Route('/{id}/valider', name: 'app_affectation_valider', methods: ['POST'])]
    public function valider(
        Request $request,
        Affectation $affectation,
        CarriereService $carriereService
    ): Response {
        $this->denyAccessUnlessGranted(AffectationVoter::VALIDATE, $affectation);

        if ($this->isCsrfTokenValid('valider' . $affectation->getId(), $request->request->get('_token'))) {
            try {
                $carriereService->validerMouvement($affectation);
                $this->addFlash('success', 'Mouvement validé avec succès. En attente de prise de service.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_affectation_show', ['id' => $affectation->getId()]);
    }

    #[Route('/{id}/prise-service', name: 'app_affectation_prise_service', methods: ['GET', 'POST'])]
    public function priseService(
        Request $request,
        Affectation $affectation,
        CarriereService $carriereService
    ): Response {
        $this->denyAccessUnlessGranted(AffectationVoter::PRISE_SERVICE, $affectation);

        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('prise_service' . $affectation->getId(), $request->request->get('_token'))) {
                $dateStr = $request->request->get('date_prise_service');
                $datePrise = $dateStr ? new \DateTime($dateStr) : new \DateTime();

                try {
                    $carriereService->confirmerPriseDeService($affectation, $datePrise);
                    $this->addFlash('success', sprintf('Prise de service confirmée au %s. Le poste et le dossier de l\'agent sont mis à jour.', $datePrise->format('d/m/Y')));
                    return $this->redirectToRoute('app_affectation_show', ['id' => $affectation->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la prise de service : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Jeton CSRF invalide.');
            }
        }

        return $this->render('affectation/prise_service.html.twig', [
            'affectation' => $affectation,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_affectation_annuler', methods: ['POST'])]
    public function annuler(
        Request $request,
        Affectation $affectation,
        CarriereService $carriereService
    ): Response {
        $this->denyAccessUnlessGranted(AffectationVoter::CANCEL, $affectation);

        if ($this->isCsrfTokenValid('annuler' . $affectation->getId(), $request->request->get('_token'))) {
            $motif = $request->request->get('motif_annulation');
            try {
                $carriereService->annulerMouvement($affectation, $motif);
                $this->addFlash('info', 'Le mouvement a été annulé.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'annulation : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_affectation_show', ['id' => $affectation->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_affectation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Affectation $affectation,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted(AffectationVoter::DELETE, $affectation);

        if ($this->isCsrfTokenValid('delete' . $affectation->getId(), $request->request->get('_token'))) {
            $em->remove($affectation);
            $em->flush();
            $this->addFlash('success', 'Affectation supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_affectation_index');
    }
}
