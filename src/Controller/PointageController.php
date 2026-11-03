<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\Pointage;
use App\Form\PointageType;
use App\Repository\EmployeRepository;
use App\Repository\PointageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin/pointage')]
class PointageController extends AbstractController
{
    #[Route('/', name: 'app_pointage_index', methods: ['GET'])]
    public function index(PointageRepository $pointageRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté et son institution
        $user = $this->getUser();
        $institution = $user->getInstitution();
        
        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution pour voir les pointages.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Récupérer la date actuelle
        $today = new \DateTime();
        $formattedDate = $today->format('Y-m-d');
        
        // Statistiques générales pour l'institution
        $totalEmployes = $entityManager->getRepository(Employe::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.user', 'u')
            ->where('u.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
            
        $presentsAujourdhui = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Présent', $today, $institution);
        $absentsAujourdhui = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Absent', $today, $institution);
        $retardsAujourdhui = $pointageRepository->countPointagesParStatutEtDateEtInstitution('En retard', $today, $institution);
        $congesAujourdhui = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Congé', $today, $institution);
        
        // Tendances (comparaison avec la semaine précédente)
        $lastWeek = (clone $today)->modify('-7 days');
        $presentsLastWeek = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Présent', $lastWeek, $institution);
        $absentsLastWeek = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Absent', $lastWeek, $institution);
        
        // Calculer les tendances en pourcentage
        $tendancePresents = $presentsLastWeek > 0 ? round((($presentsAujourdhui - $presentsLastWeek) / $presentsLastWeek) * 100) : 0;
        $tendanceAbsents = $absentsLastWeek > 0 ? round((($absentsAujourdhui - $absentsLastWeek) / $absentsLastWeek) * 100) : 0;
        
        // Pointages du jour filtrés par institution
        $pointagesJour = $pointageRepository->findByDateAndInstitution($today, $institution);
        
        return $this->render('pointage/index.html.twig', [
            'pointages' => $pointagesJour,
            'date_actuelle' => $formattedDate,
            'stats' => [
                'total_employes' => $totalEmployes,
                'presents' => $presentsAujourdhui,
                'absents' => $absentsAujourdhui,
                'retards' => $retardsAujourdhui,
                'conges' => $congesAujourdhui,
                'tendance_presents' => $tendancePresents,
                'tendance_absents' => $tendanceAbsents,
            ],
        ]);
    }
    
    #[Route('/dashboard', name: 'app_pointage_dashboard', methods: ['GET'])]
    public function dashboard(
        PointageRepository $pointageRepository, 
        ChartBuilderInterface $chartBuilder,
        EntityManagerInterface $entityManager,
        EmployeRepository $employeRepository
    ): Response
    {
        // Récupérer l'utilisateur connecté et son institution
        $user = $this->getUser();
        $institution = $user->getInstitution();
        
        if (!$institution) {
            $this->addFlash('error', 'Vous devez être associé à une institution pour accéder au dashboard.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Récupérer la date actuelle
        $today = new \DateTime();
        $currentMonth = $today->format('m');
        $currentYear = $today->format('Y');
        $formattedDate = $today->format('Y-m-d');

        // Statistiques pour les cartes filtrées par institution
        $presentsAujourdhui = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Présent', $today, $institution);
        $congesEnCours = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Congé', $today, $institution);
        $absentsAujourdhui = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Absent', $today, $institution);
        $retardsAujourdhui = $pointageRepository->countPointagesParStatutEtDateEtInstitution('En retard', $today, $institution);
        $absencesMois = $pointageRepository->countPointagesParStatutEtMoisEtInstitution('Absent', $currentMonth, $currentYear, $institution);
        $retardsMois = $pointageRepository->countPointagesParStatutEtMoisEtInstitution('En retard', $currentMonth, $currentYear, $institution);
        
        // Statistiques de la semaine/mois dernier pour comparaison
        $lastWeek = (clone $today)->modify('-7 days');
        $presentsLastWeek = $pointageRepository->countPointagesParStatutEtDateEtInstitution('Présent', $lastWeek, $institution);
        $tendancePresents = $presentsLastWeek > 0 ? round((($presentsAujourdhui - $presentsLastWeek) / $presentsLastWeek) * 100) : 0;

        $lastMonth = (clone $today)->modify('-1 month');
        $lastMonthNumber = $lastMonth->format('m');
        $lastMonthYear = $lastMonth->format('Y');
        
        $absencesLastMonth = $pointageRepository->countPointagesParStatutEtMoisEtInstitution('Absent', $lastMonthNumber, $lastMonthYear, $institution);
        $retardsLastMonth = $pointageRepository->countPointagesParStatutEtMoisEtInstitution('En retard', $lastMonthNumber, $lastMonthYear, $institution);
        
        // Calculer les tendances
        $tendanceAbsences = $absencesLastMonth > 0 ? round((($absencesMois - $absencesLastMonth) / $absencesLastMonth) * 100) : 0;
        $tendanceRetards = $retardsLastMonth > 0 ? round((($retardsMois - $retardsLastMonth) / $retardsLastMonth) * 100) : 0;
        
        // Récupérer les pointages du jour filtrés par institution
        $pointagesJour = $pointageRepository->findByDateAndInstitution($today, $institution);
        
        // Données pour le graphique d'évolution mensuelle filtrées par institution
        $statsMensuelles = $pointageRepository->getStatsMensuellesParInstitution($currentYear, $institution);
        
        $labels = [];
        $dataPresents = [];
        $dataAbsents = [];
        $dataRetards = [];
        $dataConges = [];
        
        $moisFr = [
            '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr',
            '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Août',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'
        ];
        
        // Initialiser les données pour tous les mois
        for ($i = 1; $i <= 12; $i++) {
            $mois = str_pad($i, 2, '0', STR_PAD_LEFT);
            $labels[] = $moisFr[$mois];
            $dataPresents[$i-1] = 0;
            $dataAbsents[$i-1] = 0;
            $dataRetards[$i-1] = 0;
            $dataConges[$i-1] = 0;
        }
        
        // Remplir avec les données réelles
        foreach ($statsMensuelles as $stat) {
            $mois = (int)$stat['mois'];
            switch ($stat['statut']) {
                case 'Présent':
                    $dataPresents[$mois-1] = $stat['total'];
                    break;
                case 'Absent':
                    $dataAbsents[$mois-1] = $stat['total'];
                    break;
                case 'En retard':
                    $dataRetards[$mois-1] = $stat['total'];
                    break;
                case 'Congé':
                    $dataConges[$mois-1] = $stat['total'];
                    break;
            }
        }
        
        // Créer le graphique
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Présences',
                    'backgroundColor' => 'rgba(40, 167, 69, 0.2)',
                    'borderColor' => 'rgb(40, 167, 69)',
                    'data' => $dataPresents,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Absences',
                    'backgroundColor' => 'rgba(220, 53, 69, 0.2)',
                    'borderColor' => 'rgb(220, 53, 69)',
                    'data' => $dataAbsents,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Retards',
                    'backgroundColor' => 'rgba(255, 193, 7, 0.2)',
                    'borderColor' => 'rgb(255, 193, 7)',
                    'data' => $dataRetards,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Congés',
                    'backgroundColor' => 'rgba(23, 162, 184, 0.2)',
                    'borderColor' => 'rgb(23, 162, 184)',
                    'data' => $dataConges,
                    'tension' => 0.4,
                ]
            ]
        ]);
        
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'drawBorder' => false,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'align' => 'end',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);
        
        // Récupérer les employés avec les meilleures statistiques de présence pour l'institution
        $topEmployes = $pointageRepository->findTopPresenceEmployesByInstitution(5, $institution);
        $totalEmployes = $employeRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.user', 'u')
            ->where('u.institution = :institution')
            ->setParameter('institution', $institution)
            ->getQuery()
            ->getSingleScalarResult();
            
        return $this->render('pointage/dashboard.html.twig', [
            'pointages' => $pointagesJour,
            'date_actuelle' => $today->format('Y-m-d'),
            'stats' => [
                'presents' => $presentsAujourdhui,
                'presents_aujourdhui' => $presentsAujourdhui,
                'conges' => $congesEnCours,
                'conges_en_cours' => $congesEnCours,
                'absents' => $absentsAujourdhui,
                'absences_aujourdhui' => $absentsAujourdhui,
                'absences_mois' => $absencesMois,
                'retards' => $retardsAujourdhui,
                'retards_aujourdhui' => $retardsAujourdhui,
                'retards_mois' => $retardsMois,
                'tendance_presents' => $tendancePresents,
                'tendance_absences' => $tendanceAbsences,
                'tendance_retards' => $tendanceRetards,
                'total_employes' => (int) $totalEmployes,
            ],
            'chart' => $chart,
            'top_employes' => $topEmployes
        ]);
    }
    #[Route('/new', name: 'app_pointage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('POINTAGE_CREATE');

        $pointage = new Pointage();
        // Set default values
        $pointage->setDate(new \DateTime());
        $pointage->setEstPresent(true);
        
        $form = $this->createForm(PointageType::class, $pointage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the dateEntree to current date and time
            $pointage->setDateEntree(new \DateTime());
            if ($pointage->getInstitution() === null && $pointage->getEmploye() !== null) {
                $pointage->setInstitution($pointage->getEmploye()->getInstitution());
            }
            
            $entityManager->persist($pointage);
            $entityManager->flush();

            $this->addFlash('success', 'Le pointage a été enregistré avec succès.');
            return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pointage/new.html.twig', [
            'pointage' => $pointage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pointage_show', methods: ['GET'])]
    public function show(Pointage $pointage): Response
    {
        $this->denyAccessUnlessGranted('POINTAGE_VIEW', $pointage);

        return $this->render('pointage/show.html.twig', [
            'pointage' => $pointage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pointage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pointage $pointage, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('POINTAGE_EDIT', $pointage);

        $form = $this->createForm(PointageType::class, $pointage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalculer le statut
            $this->calculateStatus($pointage);
            
            $entityManager->flush();

            $this->addFlash('success', 'Le pointage a été modifié avec succès.');
            return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pointage/edit.html.twig', [
            'pointage' => $pointage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pointage_delete', methods: ['POST'])]
    public function delete(Request $request, Pointage $pointage, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('POINTAGE_DELETE', $pointage);

        if ($this->isCsrfTokenValid('delete'.$pointage->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pointage);
            $entityManager->flush();
            $this->addFlash('success', 'Le pointage a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/employe/{id}', name: 'app_pointage_employe', methods: ['GET'])]
    public function employePointages(
        Employe $employe, 
        PointageRepository $pointageRepository,
        ChartBuilderInterface $chartBuilder
    ): Response
    {
        $this->denyAccessUnlessGranted('EMPLOYE_VIEW', $employe);
        // Récupérer tous les pointages de l'employé
        $pointages = $pointageRepository->findBy(['employe' => $employe], ['date' => 'DESC']);
        
        // Statistiques de l'employé
        $today = new \DateTime();
        $currentMonth = $today->format('m');
        $currentYear = $today->format('Y');
        
        $presencesMois = $pointageRepository->countEmployePointagesParStatutEtMois($employe, 'Présent', $currentMonth, $currentYear);
        $absencesMois = $pointageRepository->countEmployePointagesParStatutEtMois($employe, 'Absent', $currentMonth, $currentYear);
        $retardsMois = $pointageRepository->countEmployePointagesParStatutEtMois($employe, 'Retard', $currentMonth, $currentYear);
        $congesMois = $pointageRepository->countEmployePointagesParStatutEtMois($employe, 'Congé', $currentMonth, $currentYear);
        
        // Historique des 30 derniers jours pour la visualisation
        $derniers30Jours = $pointageRepository->findEmployePointagesDerniers30Jours($employe);
        
        // Préparer le calendrier du mois
        $calendar = $this->prepareCalendar($employe, $currentMonth, $currentYear, $pointageRepository);
        
        // Graphique d'assiduité
        $stats = $pointageRepository->getEmployeStatsAnnuelles($employe, $currentYear);
        
        $labels = [];
        $dataPresents = [];
        $dataAbsents = [];
        $dataRetards = [];
        
        $moisFr = [
            '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr',
            '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Août',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'
        ];
        
        // Initialiser les données pour tous les mois
        for ($i = 1; $i <= 12; $i++) {
            $mois = str_pad($i, 2, '0', STR_PAD_LEFT);
            $labels[] = $moisFr[$mois];
            $dataPresents[$i-1] = 0;
            $dataAbsents[$i-1] = 0;
            $dataRetards[$i-1] = 0;
        }
        
        // Remplir avec les données réelles
        foreach ($stats as $stat) {
            $mois = (int)$stat['mois'];
            switch ($stat['statut']) {
                case 'Présent':
                    $dataPresents[$mois-1] = $stat['total'];
                    break;
                case 'Absent':
                    $dataAbsents[$mois-1] = $stat['total'];
                    break;
                case 'Retard':
                    $dataRetards[$mois-1] = $stat['total'];
                    break;
            }
        }
        
        // Créer le graphique
        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Présences',
                    'backgroundColor' => 'rgba(40, 167, 69, 0.7)',
                    'borderColor' => 'rgb(40, 167, 69)',
                    'data' => $dataPresents,
                ],
                [
                    'label' => 'Absences',
                    'backgroundColor' => 'rgba(220, 53, 69, 0.7)',
                    'borderColor' => 'rgb(220, 53, 69)',
                    'data' => $dataAbsents,
                ],
                [
                    'label' => 'Retards',
                    'backgroundColor' => 'rgba(255, 193, 7, 0.7)',
                    'borderColor' => 'rgb(255, 193, 7)',
                    'data' => $dataRetards,
                ]
            ]
        ]);
        
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);
        
        return $this->render('pointage/employe.html.twig', [
            'employe' => $employe,
            'pointages' => $pointages,
            'stats' => [
                'presences_mois' => $presencesMois,
                'absences_mois' => $absencesMois,
                'retards_mois' => $retardsMois,
                'conges_mois' => $congesMois,
                'taux_presence' => $presencesMois > 0 ? round(($presencesMois / ($presencesMois + $absencesMois + $retardsMois + $congesMois)) * 100) : 0,
            ],
            'derniers30Jours' => $derniers30Jours,
            'calendar' => $calendar,
            'chart' => $chart,
        ]);
    }
    
    private function calculateStatus(Pointage $pointage): void
    {
        // Si absence explicite
        if (!$pointage->isEstPresent()) {
            $pointage->setStatus('Absent');
            return;
        }
        
        // Si c'est un congé
        if ($pointage->getMotifAbsence() && strpos(strtolower($pointage->getMotifAbsence()), 'congé') !== false) {
            $pointage->setStatus('Congé');
            return;
        }
        
        // Vérifier l'heure d'arrivée (si retard)
        if ($pointage->getHeureArrivee()) {
            $heureArrivee = $pointage->getHeureArrivee()->format('H:i');
            if (strtotime($heureArrivee) > strtotime('09:00')) {
                $pointage->setStatus('Retard');
                return;
            }
        }
        
        // Par défaut, considéré comme présent
        $pointage->setStatus('Présent');
    }
    
    private function prepareCalendar(Employe $employe, string $month, string $year, PointageRepository $pointageRepository): array
    {
        $calendar = [];
        $firstDay = new \DateTime("$year-$month-01");
        $lastDay = (clone $firstDay)->modify('last day of this month');
        
        $pointagesMois = $pointageRepository->findEmployePointagesByMonth($employe, $month, $year);
        
        // Indexer les pointages par jour
        $pointagesIndexed = [];
        foreach ($pointagesMois as $pointage) {
            $day = $pointage->getDate()->format('j');
            $pointagesIndexed[$day] = $pointage;
        }
        
        // Premier jour de la semaine (0 = lundi, 6 = dimanche)
        $firstDayOfWeek = (int) $firstDay->format('N') - 1;
        
        // Préparer les jours du mois
        $days = [];
        
        // Jours vides du début
        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $days[] = [
                'number' => '',
                'status' => 'empty',
                'tooltip' => '',
            ];
        }
        
        // Jours du mois
        for ($day = 1; $day <= (int) $lastDay->format('j'); $day++) {
            $date = new \DateTime("$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT));
            $isWeekend = in_array($date->format('N'), [6, 7]);
            
            $status = 'weekend';
            $tooltip = 'Weekend';
            
            if (!$isWeekend) {
                if (isset($pointagesIndexed[$day])) {
                    $pointage = $pointagesIndexed[$day];
                    $status = strtolower($pointage->getStatus());
                    $tooltip = $pointage->getStatus();
                    
                    if ($status === 'retard') {
                        $tooltip .= ' - Arrivée: ' . $pointage->getHeureArrivee()->format('H:i');
                    }
                } else {
                    // Jour de semaine sans pointage
                    $status = 'no-data';
                    $tooltip = 'Aucune donnée';
                }
            }
            
            $days[] = [
                'number' => $day,
                'status' => $status,
                'tooltip' => $tooltip,
            ];
        }
        
        // Jours vides de fin pour compléter la dernière semaine
        $remaining = 42 - count($days);
        for ($i = 0; $i < $remaining; $i++) {
            $days[] = [
                'number' => '',
                'status' => 'empty',
                'tooltip' => '',
            ];
        }
        
        // Diviser en semaines
        $weeks = array_chunk($days, 7);
        
        return [
            'month' => $firstDay->format('F Y'),
            'weeks' => $weeks,
        ];
    }
}