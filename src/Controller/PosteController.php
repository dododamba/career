<?php

namespace App\Controller;

use App\Entity\Poste;
use App\Form\PosteAffectationType;
use App\Form\PosteType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\PosteRepository;
use App\Repository\StructureRepository;
use App\Service\PosteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/poste')]
#[IsGranted('ROLE_USER')]
final class PosteController extends AbstractController
{
    #[Route(name: 'app_poste_index', methods: ['GET'])]
    public function index(
        Request $request,
        PosteRepository $posteRepository,
        StructureRepository $structureRepository,
        TenantContextInterface $tenantContext
    ): Response {
        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Aucune institution active.');
            return $this->redirectToRoute('app_login');
        }

        $query = $request->query->get('q');
        $statut = $request->query->get('statut');
        $structureId = $request->query->getInt('structure_id');

        $postes = $posteRepository->searchPostes($institution, $query, $statut, $structureId);
        $stats = $posteRepository->getPostesStatsByInstitution($institution);
        $structures = $structureRepository->findByInstitution($institution);

        return $this->render('poste/index.html.twig', [
            'postes' => $postes,
            'stats' => $stats,
            'structures' => $structures,
            'currentQuery' => $query,
            'currentStatut' => $statut,
            'currentStructure' => $structureId,
            'institution' => $institution,
        ]);
    }

    #[Route('/vacants', name: 'app_poste_vacants', methods: ['GET'])]
    public function vacants(
        PosteRepository $posteRepository,
        TenantContextInterface $tenantContext
    ): Response {
        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Aucune institution active.');
            return $this->redirectToRoute('app_login');
        }

        $vacants = $posteRepository->findVacantsByInstitution($institution);
        $stats = $posteRepository->getPostesStatsByInstitution($institution);

        return $this->render('poste/vacants.html.twig', [
            'postes' => $vacants,
            'stats' => $stats,
            'institution' => $institution,
        ]);
    }

    #[Route('/new', name: 'app_poste_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        TenantContextInterface $tenantContext
    ): Response {
        $this->denyAccessUnlessGranted('POSTE_CREATE');

        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Aucune institution active.');
            return $this->redirectToRoute('app_poste_index');
        }

        $poste = new Poste();
        $poste->setInstitution($institution);

        $form = $this->createForm(PosteType::class, $poste, [
            'institution' => $institution,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $poste->setCreatedAt(new \DateTime());
            $entityManager->persist($poste);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le poste « %s » (%s) a été créé avec succès.', $poste->getIntitule(), $poste->getReference()));

            return $this->redirectToRoute('app_poste_show', ['id' => $poste->getId()]);
        }

        return $this->render('poste/new.html.twig', [
            'poste' => $poste,
            'form' => $form,
            'institution' => $institution,
        ]);
    }

    #[Route('/{id}', name: 'app_poste_show', methods: ['GET'])]
    public function show(Poste $poste, PosteService $posteService): Response
    {
        $this->denyAccessUnlessGranted('POSTE_VIEW', $poste);

        $viewData = $posteService->getPoste360View($poste);

        return $this->render('poste/show.html.twig', $viewData);
    }

    #[Route('/{id}/edit', name: 'app_poste_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Poste $poste,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('POSTE_EDIT', $poste);

        $form = $this->createForm(PosteType::class, $poste, [
            'institution' => $poste->getInstitution(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $poste->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le poste « %s » a été mis à jour.', $poste->getIntitule()));

            return $this->redirectToRoute('app_poste_show', ['id' => $poste->getId()]);
        }

        return $this->render('poste/edit.html.twig', [
            'poste' => $poste,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_poste_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Poste $poste,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('POSTE_DELETE', $poste);

        if ($this->isCsrfTokenValid('delete' . $poste->getId(), $request->getPayload()->getString('_token'))) {
            $intitule = $poste->getIntitule();
            $entityManager->remove($poste);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le poste « %s » a été supprimé.', $intitule));
        }

        return $this->redirectToRoute('app_poste_index');
    }

    #[Route('/{id}/affecter', name: 'app_poste_affecter', methods: ['GET', 'POST'])]
    public function affecter(
        Request $request,
        Poste $poste,
        PosteService $posteService
    ): Response {
        $this->denyAccessUnlessGranted('POSTE_AFFECTER', $poste);

        $form = $this->createForm(PosteAffectationType::class, null, [
            'institution' => $poste->getInstitution(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $employe = $data['employe'];
            $dateDebut = $data['date_debut'];
            $motif = $data['motif'];
            $commentaire = $data['commentaire'];

            try {
                $posteService->affecterAgent($poste, $employe, $dateDebut, $motif, $commentaire);

                $this->addFlash('success', sprintf(
                    'L\'agent %s a été affecté avec succès au poste « %s ».',
                    $employe->getNomComplet(),
                    $poste->getIntitule()
                ));

                return $this->redirectToRoute('app_poste_show', ['id' => $poste->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'affectation : ' . $e->getMessage());
            }
        }

        return $this->render('poste/affecter.html.twig', [
            'poste' => $poste,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/liberer', name: 'app_poste_liberer', methods: ['POST'])]
    public function liberer(
        Request $request,
        Poste $poste,
        PosteService $posteService
    ): Response {
        $this->denyAccessUnlessGranted('POSTE_LIBERER', $poste);

        if ($this->isCsrfTokenValid('liberer' . $poste->getId(), $request->getPayload()->getString('_token'))) {
            $dateFin = new \DateTime($request->request->get('date_fin', 'today'));
            $motifDepart = $request->request->get('motif_depart', 'Départ du poste');

            $posteService->libererPoste($poste, $dateFin, $motifDepart);

            $this->addFlash('success', sprintf('Le poste « %s » a été libéré et est maintenant vacant.', $poste->getIntitule()));
        }

        return $this->redirectToRoute('app_poste_show', ['id' => $poste->getId()]);
    }
}
