<?php

namespace App\Controller;

use App\Entity\Institution;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ServiceApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/services/{institutionId}', name: 'app_api_services_by_institution', methods: ['GET'])]
    public function getServicesByInstitution(int $institutionId): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], 401);
        }

        // Cross-tenant verification: Only super-admin or members of this institution can view its services
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $userInstitution = $currentUser->getInstitution();
            if ($userInstitution === null || $userInstitution->getId() !== $institutionId) {
                return new JsonResponse(['error' => 'Accès interdit : cette institution ne correspond pas à votre profil'], 403);
            }
        }

        $institution = $this->entityManager->getRepository(Institution::class)->find($institutionId);
        if (!$institution) {
            return new JsonResponse(['error' => 'Institution non trouvée'], 404);
        }

        $services = $this->entityManager->getRepository(Service::class)->findBy(['institution' => $institution], ['nom' => 'ASC']);

        $data = [];
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'nom' => $service->getNom()
            ];
        }

        return new JsonResponse($data);
    }
}
