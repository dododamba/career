<?php

namespace App\Controller;

use App\Entity\OrganisationHistory;
use App\Entity\Structure;
use App\Form\StructureType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\StructureRepository;
use App\Security\Voter\StructureVoter;
use App\Service\OrganisationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/structure')]
#[IsGranted('ROLE_USER')]
final class StructureController extends AbstractController
{
    #[Route(name: 'app_structure_index', methods: ['GET'])]
    public function index(
        StructureRepository $structureRepository,
        TenantContextInterface $tenantContext
    ): Response {
        $institution = $tenantContext->getTenant();
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');

        if ($institution) {
            $structures = $structureRepository->findByInstitution($institution);
            $rootStructures = $structureRepository->findRootStructures($institution);
        } elseif ($isSuperAdmin) {
            $structures = $structureRepository->findAll();
            $rootStructures = $structureRepository->findBy(['parent' => null]);
        } else {
            $structures = [];
            $rootStructures = [];
        }

        return $this->render('structure/index.html.twig', [
            'structures' => $structures,
            'rootStructures' => $rootStructures,
        ]);
    }

    #[Route('/new', name: 'app_structure_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        TenantContextInterface $tenantContext,
        OrganisationService $organisationService
    ): Response {
        $this->denyAccessUnlessGranted('STRUCTURE_CREATE');

        $structure = new Structure();
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        $institution = $tenantContext->getTenant();

        $form = $this->createForm(StructureType::class, $structure, [
            'institution' => $institution,
            'include_institution' => $isSuperAdmin && !$tenantContext->hasTenant(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($structure->getInstitution() === null) {
                $structure->setInstitution($tenantContext->requireTenant());
            }

            try {
                // Validation de non-cyclicité et respect du tenant
                $organisationService->validateParentStructure($structure, $structure->getParent());
                $organisationService->validateResponsable($structure->getInstitution(), $structure->getResponsable());

                $entityManager->persist($structure);
                $entityManager->flush();

                $organisationService->logChange(
                    $structure->getInstitution(),
                    OrganisationHistory::ENTITY_STRUCTURE,
                    $structure->getId(),
                    $structure->getNom(),
                    OrganisationHistory::ACTION_CREATED,
                    [
                        'code' => $structure->getCode(),
                        'type' => $structure->getType(),
                        'parent' => $structure->getParent()?->getNom(),
                        'responsable' => $structure->getResponsable()?->getNom(),
                    ]
                );

                $this->addFlash('success', 'Structure créée avec succès.');
                return $this->redirectToRoute('app_structure_index', [], Response::HTTP_SEE_OTHER);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('structure/new.html.twig', [
            'structure' => $structure,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_structure_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Structure $structure): Response
    {
        $this->denyAccessUnlessGranted('STRUCTURE_VIEW', $structure);

        return $this->render('structure/show.html.twig', [
            'structure' => $structure,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_structure_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Structure $structure,
        EntityManagerInterface $entityManager,
        TenantContextInterface $tenantContext,
        OrganisationService $organisationService
    ): Response {
        $this->denyAccessUnlessGranted('STRUCTURE_EDIT', $structure);

        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        $institution = $structure->getInstitution() ?? $tenantContext->getTenant();

        $oldName = $structure->getNom();
        $oldParentId = $structure->getParent()?->getId();
        $oldResponsableId = $structure->getResponsable()?->getId();
        $oldStatut = $structure->getStatut();

        $form = $this->createForm(StructureType::class, $structure, [
            'institution' => $institution,
            'current_structure_id' => $structure->getId(),
            'include_institution' => $isSuperAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Validation de non-cyclicité et cohérence tenant
                $organisationService->validateParentStructure($structure, $structure->getParent());
                $organisationService->validateResponsable($structure->getInstitution(), $structure->getResponsable());

                $entityManager->flush();

                $details = [];
                if ($oldName !== $structure->getNom()) {
                    $details['nom'] = ['old' => $oldName, 'new' => $structure->getNom()];
                }
                if ($oldParentId !== $structure->getParent()?->getId()) {
                    $details['parent'] = ['old' => $oldParentId, 'new' => $structure->getParent()?->getId()];
                }
                if ($oldResponsableId !== $structure->getResponsable()?->getId()) {
                    $details['responsable'] = ['old' => $oldResponsableId, 'new' => $structure->getResponsable()?->getId()];
                }
                if ($oldStatut !== $structure->getStatut()) {
                    $details['statut'] = ['old' => $oldStatut, 'new' => $structure->getStatut()];
                }

                $organisationService->logChange(
                    $structure->getInstitution(),
                    OrganisationHistory::ENTITY_STRUCTURE,
                    $structure->getId(),
                    $structure->getNom(),
                    OrganisationHistory::ACTION_UPDATED,
                    $details
                );

                $this->addFlash('success', 'Structure modifiée avec succès.');
                return $this->redirectToRoute('app_structure_index', [], Response::HTTP_SEE_OTHER);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('structure/edit.html.twig', [
            'structure' => $structure,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_structure_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        Structure $structure,
        EntityManagerInterface $entityManager,
        OrganisationService $organisationService
    ): Response {
        $this->denyAccessUnlessGranted('STRUCTURE_DELETE', $structure);

        if ($this->isCsrfTokenValid('delete'.$structure->getId(), $request->getPayload()->getString('_token'))) {
            // Vérification des dépendances
            if ($structure->getChildren()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer cette structure car elle possède des sous-structures.');
                return $this->redirectToRoute('app_structure_index', [], Response::HTTP_SEE_OTHER);
            }

            if ($structure->getServices()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer cette structure car des services y sont rattachés.');
                return $this->redirectToRoute('app_structure_index', [], Response::HTTP_SEE_OTHER);
            }

            $inst = $structure->getInstitution();
            $sId = $structure->getId();
            $sNom = $structure->getNom();

            $entityManager->remove($structure);
            $entityManager->flush();

            $organisationService->logChange(
                $inst,
                OrganisationHistory::ENTITY_STRUCTURE,
                $sId,
                $sNom,
                OrganisationHistory::ACTION_DELETED
            );

            $this->addFlash('success', 'Structure supprimée avec succès.');
        }

        return $this->redirectToRoute('app_structure_index', [], Response::HTTP_SEE_OTHER);
    }
}
