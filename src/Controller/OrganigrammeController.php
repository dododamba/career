<?php

namespace App\Controller;

use App\Entity\Institution;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\InstitutionRepository;
use App\Service\OrganisationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/organigramme')]
#[IsGranted('ROLE_USER')]
final class OrganigrammeController extends AbstractController
{
    #[Route(name: 'app_organigramme_index', methods: ['GET'])]
    public function index(
        Request $request,
        TenantContextInterface $tenantContext,
        InstitutionRepository $institutionRepository,
        OrganisationService $organisationService
    ): Response {
        $user = $this->getUser();
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');

        // Sélection de l'institution
        $institutionId = $request->query->getInt('institution_id');
        if ($institutionId > 0 && $isSuperAdmin) {
            $institution = $institutionRepository->find($institutionId);
        } else {
            $institution = $tenantContext->getTenant();
        }

        if ($institution === null && $isSuperAdmin) {
            $firstInst = $institutionRepository->findOneBy([], ['nom' => 'ASC']);
            $institution = $firstInst;
        }

        $allInstitutions = $isSuperAdmin ? $institutionRepository->findAll() : [];

        $treeData = null;
        $overview = null;
        if ($institution !== null) {
            $treeData = $organisationService->buildOrganigrammeTree($institution);
            $overview = $organisationService->getOrganisationOverview($institution);
        }

        return $this->render('organigramme/index.html.twig', [
            'institution' => $institution,
            'allInstitutions' => $allInstitutions,
            'treeData' => $treeData,
            'overview' => $overview,
        ]);
    }

    #[Route('/data', name: 'app_organigramme_data', methods: ['GET'])]
    public function data(
        Request $request,
        TenantContextInterface $tenantContext,
        InstitutionRepository $institutionRepository,
        OrganisationService $organisationService
    ): JsonResponse {
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        $institutionId = $request->query->getInt('institution_id');

        if ($institutionId > 0 && $isSuperAdmin) {
            $institution = $institutionRepository->find($institutionId);
        } else {
            $institution = $tenantContext->getTenant();
        }

        if ($institution === null) {
            return new JsonResponse(['error' => 'Aucune institution sélectionnée'], Response::HTTP_NOT_FOUND);
        }

        $tree = $organisationService->buildOrganigrammeTree($institution);

        return new JsonResponse($tree);
    }
}
