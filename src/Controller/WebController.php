<?php

namespace App\Controller;

use App\Repository\CongesLogRepository;
use App\Repository\SecurityAuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WebController extends AbstractController
{
    #[Route('/', name: 'app_web')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route('/log', name: 'app_log')]
    #[IsGranted('ROLE_ADMIN')]
    public function log(
        CongesLogRepository $congesLogRepository,
        SecurityAuditLogRepository $securityAuditLogRepository
    ): Response {
        $securityLogs = $securityAuditLogRepository->findLatest(100);
        $congesLogs = $congesLogRepository->findLatest(50);

        return $this->render('log.html.twig', [
            'securityLogs' => $securityLogs,
            'congesLogs' => $congesLogs,
            'logs' => $congesLogs, // for backward compatibility
        ]);
    }
}
