<?php

namespace App\Controller;

use App\Entity\OrganisationHistory;
use App\Entity\Service;
use App\Form\ServiceType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\ServiceRepository;
use App\Service\OrganisationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/service')]
#[IsGranted('ROLE_USER')]
final class ServiceController extends AbstractController
{
    #[Route(name: 'app_service_index', methods: ['GET'])]
    public function index(ServiceRepository $serviceRepository): Response
    {
        return $this->render('service/index.html.twig', [
            'services' => $serviceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        TenantContextInterface $tenantContext,
        OrganisationService $organisationService
    ): Response {
        $this->denyAccessUnlessGranted('SERVICE_CREATE');

        $service = new Service();
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        $institution = $tenantContext->getTenant();

        $form = $this->createForm(ServiceType::class, $service, [
            'institution' => $institution,
            'include_institution' => $isSuperAdmin && !$tenantContext->hasTenant(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($service->getInstitution() === null) {
                $service->setInstitution($tenantContext->requireTenant());
            }

            try {
                $organisationService->validateResponsable($service->getInstitution(), $service->getResponsable());

                $entityManager->persist($service);
                $entityManager->flush();

                $organisationService->logChange(
                    $service->getInstitution(),
                    OrganisationHistory::ENTITY_SERVICE,
                    $service->getId(),
                    $service->getNom(),
                    OrganisationHistory::ACTION_CREATED,
                    ['code' => $service->getCode(), 'structure' => $service->getStructure()?->getNom()]
                );

                $this->addFlash('success', 'Service créé avec succès.');
                return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('service/new.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_service_show', methods: ['GET'])]
    public function show(Service $service): Response
    {
        $this->denyAccessUnlessGranted('SERVICE_VIEW', $service);

        return $this->render('service/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_service_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Service $service,
        EntityManagerInterface $entityManager,
        TenantContextInterface $tenantContext,
        OrganisationService $organisationService
    ): Response {
        $this->denyAccessUnlessGranted('SERVICE_EDIT', $service);

        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        $institution = $service->getInstitution() ?? $tenantContext->getTenant();

        $oldName = $service->getNom();
        $oldStructureId = $service->getStructure()?->getId();
        $oldResponsableId = $service->getResponsable()?->getId();

        $form = $this->createForm(ServiceType::class, $service, [
            'institution' => $institution,
            'include_institution' => $isSuperAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $organisationService->validateResponsable($service->getInstitution(), $service->getResponsable());

                $entityManager->flush();

                $details = [];
                if ($oldName !== $service->getNom()) {
                    $details['name'] = ['old' => $oldName, 'new' => $service->getNom()];
                }
                if ($oldStructureId !== $service->getStructure()?->getId()) {
                    $details['structure'] = ['old' => $oldStructureId, 'new' => $service->getStructure()?->getId()];
                }
                if ($oldResponsableId !== $service->getResponsable()?->getId()) {
                    $details['responsable'] = ['old' => $oldResponsableId, 'new' => $service->getResponsable()?->getId()];
                }

                $organisationService->logChange(
                    $service->getInstitution(),
                    OrganisationHistory::ENTITY_SERVICE,
                    $service->getId(),
                    $service->getNom(),
                    OrganisationHistory::ACTION_UPDATED,
                    $details
                );

                $this->addFlash('success', 'Service modifié avec succès.');
                return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('service/edit.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_service_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Service $service,
        EntityManagerInterface $entityManager,
        OrganisationService $organisationService
    ): Response {
        $this->denyAccessUnlessGranted('SERVICE_DELETE', $service);

        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->getPayload()->getString('_token'))) {
            if ($service->getEmployes()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer ce service car il contient des employés rattachés.');
                return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
            }

            $inst = $service->getInstitution();
            $sId = $service->getId();
            $sNom = $service->getNom();

            $entityManager->remove($service);
            $entityManager->flush();

            $organisationService->logChange(
                $inst,
                OrganisationHistory::ENTITY_SERVICE,
                $sId,
                $sNom,
                OrganisationHistory::ACTION_DELETED
            );

            $this->addFlash('success', 'Service supprimé avec succès.');
        }

        return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
    }
}
