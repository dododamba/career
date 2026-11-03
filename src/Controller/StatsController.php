<?php

namespace App\Controller;

use App\Repository\EmployeRepository;
use App\Repository\PointageRepository;
use App\Repository\CongesRepository;
use App\Repository\VisiteRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/admin/statistique')]
class StatsController extends AbstractController
{
    private $employeRepository;
    private $pointageRepository;
    private $congesRepository;
    private $visiteRepository;
    private $serviceRepository;

    public function __construct(
        EmployeRepository $employeRepository,
        PointageRepository $pointageRepository,
        CongesRepository $congesRepository,
        VisiteRepository $visiteRepository,
        ServiceRepository $serviceRepository
    ) {
        $this->employeRepository = $employeRepository;
        $this->pointageRepository = $pointageRepository;
        $this->congesRepository = $congesRepository;
        $this->visiteRepository = $visiteRepository;
        $this->serviceRepository = $serviceRepository;
    }

    #[Route('/', name: 'app_stats_index')]
    public function index(Request $request): Response
    {
        // Récupérer l'utilisateur connecté et son institution
        $user = $this->getUser();
        if (!$user || !$user->getInstitution()) {
            throw new AccessDeniedException('Vous devez être associé à une institution pour accéder aux statistiques.');
        }

        $institution = $user->getInstitution();
        $today = new \DateTime('today');
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        // Pagination pour la liste des employés
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // 1. Statistiques principales
        $totalEmployes = $this->employeRepository->countByInstitution($institution);
        $employesActifs = $this->employeRepository->countActiveByInstitution($institution);
        
        // Pointages du jour
        $presentsAujourdhui = $this->pointageRepository->countPresentsTodayByInstitution($institution, $today);
        $absentsAujourdhui = $employesActifs - $presentsAujourdhui;
        
        // Congés
        $employesEnConge = $this->congesRepository->countActiveLeavesbyInstitution($institution);
        $congesEnAttente = $this->congesRepository->countPendingByInstitution($institution);
        
        // Visites
        $visitesAujourdhui = $this->visiteRepository->countTodayByInstitution($institution, $today);
        $visitesEnCours = $this->visiteRepository->countPendingByInstitution($institution, $today);
        
        // Services
        $totalServices = $this->serviceRepository->countByInstitution($institution);
        
        // Calcul des taux
        $tauxPresence = $employesActifs > 0 ? ($presentsAujourdhui / $employesActifs) * 100 : 0;
        $tauxAbsenteisme = $employesActifs > 0 ? ($absentsAujourdhui / $employesActifs) * 100 : 0;

        // 2. Statistiques hebdomadaires et mensuelles
        $tauxPresenceSemaine = $this->calculateWeeklyPresenceRate($institution);
        $tauxPresenceMois = $this->calculateMonthlyPresenceRate($institution);
        $evolutionSemaine = $this->calculateWeeklyEvolution($institution);
        $joursOuvres = $this->countWorkingDays($currentYear, $currentMonth);

        // 3. Données pour les graphiques
        $chartPresence = $this->getPresenceChartData($institution);
        $chartConges = $this->getLeaveChartData($institution);
        $topAbsencesServices = $this->getTopAbsencesByService($institution);
        $statsMonthly = $this->getMonthlyStats($institution, $currentYear);
        
        // 4. Statistiques par service
        $statsParService = $this->getServiceStats($institution, $today);
        
        // 5. Activités récentes
        $activitesRecentes = $this->getRecentActivities($institution);
        
        // 6. Liste des employés avec pagination
        $employes = $this->getEmployeesWithStatus($institution, $limit, $offset);
        $totalPages = ceil($totalEmployes / $limit);

        // 7. Services pour le filtre
        $services = $this->serviceRepository->findByInstitution($institution);

        // Compilation des statistiques
        $stats = [
            'totalEmployes' => $totalEmployes,
            'employesActifs' => $employesActifs,
            'presentsAujourdhui' => $presentsAujourdhui,
            'absentsAujourdhui' => $absentsAujourdhui,
            'employesEnConge' => $employesEnConge,
            'congesEnAttente' => $congesEnAttente,
            'visitesAujourdhui' => $visitesAujourdhui,
            'visitesEnCours' => $visitesEnCours,
            'totalServices' => $totalServices,
            'tauxPresence' => $tauxPresence,
            'tauxAbsenteisme' => $tauxAbsenteisme,
            'tauxPresenceSemaine' => $tauxPresenceSemaine,
            'tauxPresenceMois' => $tauxPresenceMois,
            'evolutionSemaine' => $evolutionSemaine,
            'joursOuvres' => $joursOuvres,
            'chartPresence' => $chartPresence,
            'chartConges' => $chartConges,
            'topAbsencesServices' => $topAbsencesServices,
            'statsMonthly' => $statsMonthly,
            'statsParService' => $statsParService,
            'activitesRecentes' => $activitesRecentes,
            'employes' => $employes,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages
            ]
        ];

        return $this->render('statistique/index.html.twig', [
            'stats' => $stats,
            'services' => $services
        ]);
    }

    /**
     * Calcule le taux de présence hebdomadaire
     */
    private function calculateWeeklyPresenceRate($institution): float
    {
        $startOfWeek = new \DateTime('monday this week');
        $endOfWeek = new \DateTime('sunday this week');
        
        $totalPointages = 0;
        $totalPresents = 0;
        
        $currentDate = clone $startOfWeek;
        while ($currentDate <= $endOfWeek && $currentDate <= new \DateTime()) {
            if ($currentDate->format('N') < 6) { // Jours ouvrables seulement
                $presents = $this->pointageRepository->countPresentsTodayByInstitution($institution, $currentDate);
                $totalEmployes = $this->employeRepository->countActiveByInstitution($institution);
                
                $totalPointages += $totalEmployes;
                $totalPresents += $presents;
            }
            $currentDate->modify('+1 day');
        }
        
        return $totalPointages > 0 ? ($totalPresents / $totalPointages) * 100 : 0;
    }

    /**
     * Calcule le taux de présence mensuel
     */
    private function calculateMonthlyPresenceRate($institution): float
    {
        $startOfMonth = new \DateTime('first day of this month');
        $today = new \DateTime();
        
        $totalPointages = 0;
        $totalPresents = 0;
        
        $currentDate = clone $startOfMonth;
        while ($currentDate <= $today) {
            if ($currentDate->format('N') < 6) { // Jours ouvrables seulement
                $presents = $this->pointageRepository->countPresentsTodayByInstitution($institution, $currentDate);
                $totalEmployes = $this->employeRepository->countActiveByInstitution($institution);
                
                $totalPointages += $totalEmployes;
                $totalPresents += $presents;
            }
            $currentDate->modify('+1 day');
        }
        
        return $totalPointages > 0 ? ($totalPresents / $totalPointages) * 100 : 0;
    }

    /**
     * Calcule l'évolution par rapport à la semaine précédente
     */
    private function calculateWeeklyEvolution($institution): float
    {
        $currentWeekRate = $this->calculateWeeklyPresenceRate($institution);
        
        // Calculer pour la semaine précédente
        $startOfLastWeek = new \DateTime('monday last week');
        $endOfLastWeek = new \DateTime('sunday last week');
        
        $totalPointages = 0;
        $totalPresents = 0;
        
        $currentDate = clone $startOfLastWeek;
        while ($currentDate <= $endOfLastWeek) {
            if ($currentDate->format('N') < 6) {
                $presents = $this->pointageRepository->countPresentsTodayByInstitution($institution, $currentDate);
                $totalEmployes = $this->employeRepository->countActiveByInstitution($institution);
                
                $totalPointages += $totalEmployes;
                $totalPresents += $presents;
            }
            $currentDate->modify('+1 day');
        }
        
        $lastWeekRate = $totalPointages > 0 ? ($totalPresents / $totalPointages) * 100 : 0;
        
        return $currentWeekRate - $lastWeekRate;
    }

    /**
     * Compte les jours ouvrables du mois
     */
    private function countWorkingDays(int $year, int $month): int
    {
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');
        
        $days = 0;
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            if ($currentDate->format('N') < 6) { // Lundi à Vendredi
                $days++;
            }
            $currentDate->modify('+1 day');
        }
        
        return $days;
    }

    /**
     * Récupère les données pour le graphique de présence
     */
    private function getPresenceChartData($institution): array
    {
        $data = $this->pointageRepository->getPresenceRateByMonthAndInstitution($institution, 6);
        
        $labels = [];
        $values = [];
        
        foreach ($data as $item) {
            $labels[] = $item['month'];
            $values[] = $item['rate'];
        }
        
        // Si pas assez de données, afficher les 6 derniers mois avec taux 0
        if (count($labels) < 6) {
            $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
            $currentMonth = (int) date('n'); // 1-12
            $currentYear = (int) date('Y');
            
            $labels = [];
            $values = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $monthNum = $currentMonth - $i;
                $year = $currentYear;
                if ($monthNum <= 0) {
                    $monthNum += 12;
                    $year--;
                }
                $labels[] = $months[$monthNum - 1] . ' ' . $year;
                $values[] = 0.0;
            }
        }
        
        return [
            'labels' => $labels,
            'data' => $values
        ];
    }

    /**
     * Récupère les données pour le graphique des congés
     */
    private function getLeaveChartData($institution): array
    {
        $data = $this->congesRepository->countByStatusAndInstitution($institution);
        
        $labels = [];
        $values = [];
        
        foreach ($data as $item) {
            $labels[] = $item['label'];
            $values[] = $item['value'];
        }
        
        // Si pas de données
        if (empty($labels)) {
            $labels = ['En attente', 'Approuvé', 'Refusé', 'Annulé'];
            $values = [5, 12, 3, 1];
        }
        
        return [
            'labels' => $labels,
            'data' => $values
        ];
    }

    /**
     * Récupère les services avec le plus d'absences
     */
    private function getTopAbsencesByService($institution): array
    {
        $data = $this->pointageRepository->getTopAbsencesByServiceAndInstitution($institution, 30);
        
        $labels = [];
        $values = [];
        
        foreach ($data as $item) {
            $labels[] = $item['service'];
            $values[] = $item['absences'];
        }
        
        // Limiter à 5 services
        $labels = array_slice($labels, 0, 5);
        $values = array_slice($values, 0, 5);
        
        // Si pas assez de données
        if (count($labels) < 5) {
            $services = $this->serviceRepository->findByInstitution($institution);
            foreach ($services as $service) {
                if (!in_array($service->getNom(), $labels)) {
                    $labels[] = $service->getNom();
                    $values[] = 0;
                    if (count($labels) >= 5) break;
                }
            }
        }
        
        return [
            'labels' => $labels,
            'data' => $values
        ];
    }

    /**
     * Récupère les statistiques mensuelles
     */
    private function getMonthlyStats($institution, int $year): array
    {
        $presents = [];
        $absents = [];
        $conges = [];
        
        for ($month = 1; $month <= 12; $month++) {
            // Calculer pour chaque mois
            $startDate = new \DateTime("$year-$month-01");
            $endDate = (clone $startDate)->modify('last day of this month');
            
            $monthPresents = 0;
            $monthAbsents = 0;
            $monthConges = 0;
            
            // Pour simplifier, on prend une moyenne du mois
            $currentDate = clone $startDate;
            $daysCount = 0;
            
            while ($currentDate <= $endDate && $currentDate <= new \DateTime()) {
                if ($currentDate->format('N') < 6) {
                    $dayPresents = $this->pointageRepository->countPresentsTodayByInstitution($institution, $currentDate);
                    $totalEmployes = $this->employeRepository->countActiveByInstitution($institution);
                    $dayConges = $this->congesRepository->countActiveLeavesbyInstitution($institution);
                    
                    $monthPresents += $dayPresents;
                    $monthAbsents += ($totalEmployes - $dayPresents - $dayConges);
                    $monthConges += $dayConges;
                    $daysCount++;
                }
                $currentDate->modify('+1 day');
            }
            
            // Moyennes mensuelles
            $presents[] = $daysCount > 0 ? round($monthPresents / $daysCount) : 0;
            $absents[] = $daysCount > 0 ? round($monthAbsents / $daysCount) : 0;
            $conges[] = $daysCount > 0 ? round($monthConges / $daysCount) : 0;
        }
        
        return [
            'presents' => $presents,
            'absents' => $absents,
            'conges' => $conges
        ];
    }

    /**
     * Récupère les statistiques par service
     */
    private function getServiceStats($institution, \DateTime $date): array
    {
        $services = $this->serviceRepository->findByInstitution($institution);
        $stats = [];
        
        foreach ($services as $service) {
            $totalEmployes = $this->employeRepository->countByService($service);
            $presents = $this->pointageRepository->countPresentsByServiceAndDate($service, $date);
            $absents = $totalEmployes - $presents;
            $tauxPresence = $totalEmployes > 0 ? ($presents / $totalEmployes) * 100 : 0;
            
            $stats[] = [
                'service' => $service->getNom(),
                'total' => $totalEmployes,
                'presents' => $presents,
                'absents' => $absents,
                'tauxPresence' => round($tauxPresence, 1)
            ];
        }
        
        // Trier par taux d'absence décroissant
        usort($stats, function($a, $b) {
            return $b['absents'] <=> $a['absents'];
        });
        
        return array_slice($stats, 0, 10); // Top 10 services
    }

    /**
     * Récupère les activités récentes
     */
    private function getRecentActivities($institution): array
    {
        $activities = [];
        $now = new \DateTime();
        
        // Derniers pointages
        $recentPointages = $this->pointageRepository->findLatestByInstitution($institution, 5);
        foreach ($recentPointages as $pointage) {
            $activities[] = [
                'title' => 'Pointage',
                'description' => sprintf('%s %s - %s', 
                    $pointage->getEmploye()->getPrenom(),
                    $pointage->getEmploye()->getNom(),
                    $pointage->getStatus()
                ),
                'time' => $pointage->getHeureArrivee() ?? $pointage->getDate(),
                'color' => $pointage->getEstPresent() ? '#2ecc71' : '#e74c3c'
            ];
        }
        
        // Dernières visites
        $recentVisites = $this->visiteRepository->findByInstitution($institution, 5, 0);
        foreach ($recentVisites as $visite) {
            $activities[] = [
                'title' => 'Visite',
                'description' => sprintf('Visiteur: %s %s', 
                    $visite->getEmploye()->getPrenom(),
                    $visite->getEmploye()->getNom()
                ),
                'time' => $visite->getArrivee(),
                'color' => '#3498db'
            ];
        }
        
        // Derniers congés
        $recentConges = $this->congesRepository->findByInstitution($institution, 3, 0);
        foreach ($recentConges as $conge) {
            $statusText = ['En attente', 'Approuvé', 'Refusé', 'Annulé'][$conge->getStatut()] ?? 'Inconnu';
            $activities[] = [
                'title' => 'Demande de congé',
                'description' => sprintf('%s %s - %s', 
                    $conge->getEmploye()->getPrenom(),
                    $conge->getEmploye()->getNom(),
                    $statusText
                ),
                'time' => $conge->getCreatedAt() ?? $now,
                'color' => '#f39c12'
            ];
        }
        
        // Trier par date décroissante
        usort($activities, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        return array_slice($activities, 0, 10);
    }

    /**
     * Récupère les employés avec leur statut actuel
     */
    private function getEmployeesWithStatus($institution, int $limit, int $offset): array
    {
        $employes = $this->employeRepository->findByInstitution($institution, $limit, $offset);
        $today = new \DateTime('today');
        $result = [];
        
        foreach ($employes as $employe) {
            // Déterminer le statut actuel
            $pointage = $this->pointageRepository->findByEmployeAndDate($employe->getId(), $today);
            $conge = $this->congesRepository->findActiveByEmployeAndDate($employe, $today);
            
            $statutAujourdhui = 'non_pointe';
            if ($conge) {
                $statutAujourdhui = 'conge';
            } elseif ($pointage) {
                if ($pointage->getEstPresent()) {
                    $statutAujourdhui = $pointage->getStatus() === 'Retard' ? 'retard' : 'present';
                } else {
                    $statutAujourdhui = 'absent';
                }
            }
            
            // Dernière présence
            $dernierePresence = $this->pointageRepository->findLastPresenceByEmploye($employe);
            
            $employeData = $employe;
            $employeData->statutAujourdhui = $statutAujourdhui;
            $employeData->dernierePresence = $dernierePresence ? $dernierePresence->getDate() : null;
            
            $result[] = $employeData;
        }
        
        return $result;
    }

    /**
     * Export des statistiques
     */
    #[Route('/export', name: 'app_stats_export')]
    public function export(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getInstitution()) {
            throw new AccessDeniedException('Accès refusé.');
        }

        $institution = $user->getInstitution();
        $range = $request->query->get('range', 'month');
        
        // Ici, implémenter la logique d'export (CSV, PDF, Excel)
        // Pour l'instant, on retourne un simple CSV
        
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="statistiques_' . date('Y-m-d') . '.csv"');
        
        // Générer le contenu CSV
        $content = $this->generateCsvContent($institution, $range);
        $response->setContent($content);
        
        return $response;
    }

    /**
     * Génère le contenu CSV pour l'export
     */
    private function generateCsvContent($institution, $range): string
    {
        $csv = "Statistiques - " . $institution->getNom() . "\n";
        $csv .= "Date d'export: " . date('Y-m-d H:i:s') . "\n";
        $csv .= "Période: " . $range . "\n\n";
        
        // En-têtes
        $csv .= "Métrique;Valeur\n";
        
        // Données
        $totalEmployes = $this->employeRepository->countByInstitution($institution);
        $employesActifs = $this->employeRepository->countActiveByInstitution($institution);
        $today = new \DateTime('today');
        $presentsAujourdhui = $this->pointageRepository->countPresentsTodayByInstitution($institution, $today);
        
        $csv .= "Total employés;" . $totalEmployes . "\n";
        $csv .= "Employés actifs;" . $employesActifs . "\n";
        $csv .= "Présents aujourd'hui;" . $presentsAujourdhui . "\n";
        $csv .= "Taux de présence;" . round(($presentsAujourdhui / $employesActifs) * 100, 2) . "%\n";
        
        return $csv;
    }
}