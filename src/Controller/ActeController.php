<?php

namespace App\Controller;

use App\Entity\Acte;
use App\Entity\Affectation;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\TypeActe;
use App\Form\ActeType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\ActeRepository;
use App\Repository\AffectationRepository;
use App\Repository\DocumentRepository;
use App\Repository\EmployeRepository;
use App\Repository\TypeActeRepository;
use App\Security\Voter\ActeVoter;
use App\Service\ActeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/acte')]
#[IsGranted('ROLE_USER')]
final class ActeController extends AbstractController
{
    #[Route(name: 'app_acte_index', methods: ['GET'])]
    public function index(
        Request $request,
        ActeRepository $acteRepository,
        TypeActeRepository $typeActeRepository,
        TenantContextInterface $tenantContext,
        EmployeRepository $employeRepository
    ): Response {
        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Aucune institution active.');
            return $this->redirectToRoute('app_login');
        }

        $typeId = $request->query->getInt('type_id');
        $statut = $request->query->get('statut');
        $search = $request->query->get('q');
        $annee = $request->query->getInt('annee');
        $employeId = $request->query->getInt('employe_id');

        $selectedEmploye = null;
        if ($employeId > 0) {
            $selectedEmploye = $employeRepository->find($employeId);
            if ($selectedEmploye && $selectedEmploye->getInstitution()?->getId() !== $institution->getId()) {
                $selectedEmploye = null;
            }
        }

        $actes = $acteRepository->findByInstitution(
            institution: $institution,
            typeActeId: $typeId > 0 ? $typeId : null,
            statut: $statut,
            search: $search,
            employe: $selectedEmploye,
            annee: $annee > 1900 ? $annee : null
        );

        $stats = $acteRepository->getStatsByInstitution($institution);
        $typesActes = $typeActeRepository->findAllActive($institution);

        return $this->render('acte/index.html.twig', [
            'actes' => $actes,
            'stats' => $stats,
            'typesActes' => $typesActes,
            'currentTypeId' => $typeId,
            'currentStatut' => $statut,
            'currentAnnee' => $annee > 1900 ? $annee : null,
            'search' => $search,
            'selectedEmploye' => $selectedEmploye,
            'institution' => $institution,
        ]);
    }

    #[Route('/nouveau', name: 'app_acte_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
        TenantContextInterface $tenantContext,
        EmployeRepository $employeRepository,
        AffectationRepository $affectationRepository,
        TypeActeRepository $typeActeRepository,
        DocumentRepository $documentRepository,
        ActeService $acteService
    ): Response {
        $this->denyAccessUnlessGranted(ActeVoter::CREATE, Acte::class);

        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();
        if (!$institution) {
            $this->addFlash('error', 'Institution introuvable.');
            return $this->redirectToRoute('app_acte_index');
        }

        $acte = new Acte();
        $acte->setInstitution($institution);
        $acte->setCreePar($this->getUser());

        // Pré-remplissage via paramètres d'URL (ex: depuis fiche agent, affectation ou document)
        $employeId = $request->query->getInt('employe_id');
        $isAgentPreset = false;
        if ($employeId > 0) {
            $employe = $employeRepository->find($employeId);
            if ($employe && $employe->getInstitution()?->getId() === $institution->getId()) {
                $acte->setEmploye($employe);
                $isAgentPreset = true;
            }
        }

        $affectationId = $request->query->getInt('affectation_id');
        if ($affectationId > 0) {
            $affectation = $affectationRepository->find($affectationId);
            if ($affectation && $affectation->getInstitution()?->getId() === $institution->getId()) {
                $acte->setAffectation($affectation);
                if (!$acte->getEmploye()) {
                    $acte->setEmploye($affectation->getEmploye());
                    $isAgentPreset = true;
                }
                $acte->setStructure($affectation->getStructure());
                $acte->setPoste($affectation->getPoste());
            }
        }

        $documentId = $request->query->getInt('document_id');
        if ($documentId > 0) {
            $doc = $documentRepository->find($documentId);
            if ($doc && $doc->getInstitution()?->getId() === $institution->getId()) {
                $acte->setDocument($doc);
            }
        }

        $typeCode = $request->query->get('type_code');
        if ($typeCode) {
            $typeActe = $typeActeRepository->findByCode($typeCode, $institution);
            if ($typeActe) {
                $acte->setTypeActe($typeActe);
            }
        }

        $form = $this->createForm(ActeType::class, $acte, [
            'institution' => $institution,
            'is_agent_preset' => $isAgentPreset,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $acte = $acteService->creerActe(
                    institution: $institution,
                    typeActe: $form->get('type_acte')->getData(),
                    numero: $form->get('numero')->getData(),
                    dateSignature: $form->get('date_signature')->getData(),
                    dateEffet: $form->get('date_effet')->getData(),
                    objet: $form->get('objet')->getData(),
                    signataireQualite: $form->get('signataire_qualite')->getData(),
                    signataireNom: $form->get('signataire_nom')->getData(),
                    statut: $form->get('statut')->getData(),
                    datePublication: $form->get('date_publication')->getData(),
                    contenuResume: $form->get('contenu_resume')->getData(),
                    employe: $isAgentPreset ? $acte->getEmploye() : $form->get('employe')->getData(),
                    poste: $form->get('poste')->getData(),
                    structure: $form->get('structure')->getData(),
                    affectation: $form->get('affectation')->getData(),
                    document: $form->get('document')->getData(),
                    acteParent: $form->get('acte_parent')->getData(),
                    currentUser: $this->getUser()
                );

                $this->addFlash('success', sprintf('L\'acte administratif N° %s a été enregistré avec succès.', $acte->getNumero()));

                if ($isAgentPreset && $acte->getEmploye()) {
                    return $this->redirectToRoute('app_employe_show', [
                        'id' => $acte->getEmploye()->getId(),
                        '_fragment' => 'tab-actes',
                    ]);
                }

                return $this->redirectToRoute('app_acte_show', ['id' => $acte->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement de l\'acte : ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', 'Validation error: ' . $error->getMessage());
            }
        } elseif ($request->isMethod('POST')) {
            $this->addFlash('error', 'POST received but form is not submitted. POST keys: ' . implode(', ', array_keys($request->request->all())));
        }

        return $this->render('acte/new.html.twig', [
            'acte' => $acte,
            'form' => $form,
            'isAgentPreset' => $isAgentPreset,
            'institution' => $institution,
        ]);
    }

    #[Route('/{id}', name: 'app_acte_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Acte $acte): Response
    {
        $this->denyAccessUnlessGranted(ActeVoter::VIEW, $acte);

        return $this->render('acte/show.html.twig', [
            'acte' => $acte,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_acte_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Acte $acte, ActeService $acteService): Response
    {
        $this->denyAccessUnlessGranted(ActeVoter::EDIT, $acte);

        $institution = $acte->getInstitution();

        $form = $this->createForm(ActeType::class, $acte, [
            'institution' => $institution,
            'current_acte_id' => $acte->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $acteService->modifierActe(
                    acte: $acte,
                    typeActe: $form->get('type_acte')->getData(),
                    numero: $form->get('numero')->getData(),
                    dateSignature: $form->get('date_signature')->getData(),
                    dateEffet: $form->get('date_effet')->getData(),
                    objet: $form->get('objet')->getData(),
                    signataireQualite: $form->get('signataire_qualite')->getData(),
                    signataireNom: $form->get('signataire_nom')->getData(),
                    statut: $form->get('statut')->getData(),
                    datePublication: $form->get('date_publication')->getData(),
                    contenuResume: $form->get('contenu_resume')->getData(),
                    employe: $form->get('employe')->getData(),
                    poste: $form->get('poste')->getData(),
                    structure: $form->get('structure')->getData(),
                    affectation: $form->get('affectation')->getData(),
                    document: $form->get('document')->getData(),
                    acteParent: $form->get('acte_parent')->getData()
                );

                $this->addFlash('success', 'L\'acte administratif a été mis à jour avec succès.');
                return $this->redirectToRoute('app_acte_show', ['id' => $acte->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('acte/edit.html.twig', [
            'acte' => $acte,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/statut', name: 'app_acte_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateStatus(Request $request, Acte $acte, ActeService $acteService): Response
    {
        $nouveauStatut = (string) $request->request->get('statut');
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('status_' . $acte->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_acte_show', ['id' => $acte->getId()]);
        }

        try {
            $acteService->changerStatut($acte, $nouveauStatut);
            $this->addFlash('success', sprintf('Le statut de l\'acte a été modifié vers : %s', $acte->getStatutLibelle()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_acte_show', ['id' => $acte->getId()]);
    }

    #[Route('/{id}/document/dissocier', name: 'app_acte_detach_doc', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function detachDocument(Request $request, Acte $acte, ActeService $acteService): Response
    {
        $this->denyAccessUnlessGranted(ActeVoter::EDIT, $acte);

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('detach_doc_' . $acte->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_acte_show', ['id' => $acte->getId()]);
        }

        $acteService->dissocierDocument($acte);
        $this->addFlash('success', 'Le document numérisé a été dissocié de l\'acte.');

        return $this->redirectToRoute('app_acte_show', ['id' => $acte->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_acte_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Acte $acte, ActeService $acteService): Response
    {
        $this->denyAccessUnlessGranted(ActeVoter::DELETE, $acte);

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_' . $acte->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_acte_show', ['id' => $acte->getId()]);
        }

        $numero = $acte->getNumero();
        $employe = $acte->getEmploye();

        try {
            $acteService->supprimerActe($acte);
            $this->addFlash('success', sprintf('L\'acte administratif N° %s a été supprimé.', $numero));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de supprimer cet acte : ' . $e->getMessage());
            return $this->redirectToRoute('app_acte_show', ['id' => $acte->getId()]);
        }

        if ($employe && $request->request->get('redirect_to_employe')) {
            return $this->redirectToRoute('app_employe_show', [
                'id' => $employe->getId(),
                '_fragment' => 'tab-actes',
            ]);
        }

        return $this->redirectToRoute('app_acte_index');
    }
}
