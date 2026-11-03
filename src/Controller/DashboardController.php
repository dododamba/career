<?php

namespace App\Controller;

use App\Entity\Conges;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Service;
use App\Repository\CongesRepository;
use App\Repository\EmployeRepository;
use App\Repository\PointageRepository;
use App\Repository\ServiceRepository;
use App\Repository\VisiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        EmployeRepository $employeRepository,
        CongesRepository $congesRepository,
        PointageRepository $pointageRepository,
        ServiceRepository $serviceRepository,
        VisiteRepository $visiteRepository
    ): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        if (!$user || !$user->getInstitution()) {
            throw new AccessDeniedException('Vous devez être associé à une institution pour accéder au tableau de bord.');
        }

        $institution = $user->getInstitution();
        $today = new \DateTime('today');
        
        // Statistiques filtrées par institution
        $stats = [
            // Total des employés de l'institution
            'totalEmployes' => $employeRepository->countByInstitution($institution),
            
            // Présents aujourd'hui dans l'institution
            'presentsAujourdhui' => $pointageRepository->countPresentsTodayByInstitution($institution, $today),
            
            // Calcul des absents (total - présents)
            'absentsAujourdhui' => $employeRepository->countActiveByInstitution($institution) 
                - $pointageRepository->countPresentsTodayByInstitution($institution, $today),
            
            // Congés en cours dans l'institution
            'congesEnCours' => $congesRepository->countActiveLeavesbyInstitution($institution),
            
            // Congés en attente de validation
            'congesEnAttente' => $congesRepository->countPendingByInstitution($institution),
            
            // Visites du jour
            'visitesAujourdhui' => $visiteRepository->countTodayByInstitution($institution, $today),
            
            // Visites en attente
            'visitesEnAttente' => $visiteRepository->countPendingByInstitution($institution, $today),
            
            // Taux de présence moyen sur 30 jours
            'tauxPresenceMoyen' => $pointageRepository->getAveragePresenceRateByInstitution($institution, 30),
            
            // Total des services de l'institution
            'totalServices' => $serviceRepository->countByInstitution($institution)
        ];

        // Taux de présence par mois (6 derniers mois) pour l'institution
        $presenceRateByMonth = $pointageRepository->getPresenceRateByMonthAndInstitution($institution, 6);
        
        // Congés par type/statut pour l'institution
        $congesByType = $congesRepository->countByStatusAndInstitution($institution);
        
        // Top services avec le plus d'absences dans l'institution
        $topAbsencesByService = $pointageRepository->getTopAbsencesByServiceAndInstitution($institution, 30);
        
        // Derniers pointages de l'institution (10 derniers)
        $derniersPointages = $pointageRepository->findLatestByInstitution($institution, 10);

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'presenceRateByMonth' => $presenceRateByMonth,
            'congesByType' => $congesByType,
            'topAbsencesByService' => $topAbsencesByService,
            'derniersPointages' => $derniersPointages,
        ]);
    }
    
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function adminDashboard(
        EmployeRepository $employeRepository,
        CongesRepository $congesRepository,
        PointageRepository $pointageRepository,
        ServiceRepository $serviceRepository
    ): Response
    {
        // Vérifier que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $this->getUser();
        $institution = $user->getInstitution();
        
        if (!$institution) {
            throw new AccessDeniedException('Vous devez être associé à une institution.');
        }
        
        // Dashboard administrateur avec vue globale de l'institution
        $stats = [
            'totalEmployes' => $employeRepository->countByInstitution($institution),
            'totalServices' => $serviceRepository->countByInstitution($institution),
            'congesEnAttente' => $congesRepository->countPendingByInstitution($institution),
            'tauxPresenceGlobal' => $pointageRepository->getGlobalPresenceRateByInstitution($institution),
        ];
        
        // Statistiques par service
        $statsByService = $pointageRepository->getStatsByServiceAndInstitution($institution);
        
        // Historique sur 12 mois
        $yearlyStats = $pointageRepository->getYearlyStatsByInstitution($institution);
        
        return $this->render('dashboard/admin.html.twig', [
            'stats' => $stats,
            'statsByService' => $statsByService,
            'yearlyStats' => $yearlyStats,
        ]);
    }
}