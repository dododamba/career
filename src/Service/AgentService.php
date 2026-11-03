<?php

namespace App\Service;

use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\Employe;
use App\Entity\Pointage;
use App\Entity\SoldeConge;
use App\Entity\Structure;
use App\Repository\ActeRepository;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;

class AgentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?ActeRepository $acteRepository = null,
        private ?MissionRepository $missionRepository = null
    ) {
    }

    /**
     * Calcule et assemble toutes les métriques et données pour la vue Fiche Agent 360°
     */
    public function getAgent360View(Employe $employe): array
    {
        $now = new \DateTime();

        // 1. Calcul de l'âge
        $age = null;
        if ($employe->getDateNaissance()) {
            $age = $employe->getDateNaissance()->diff($now)->y;
        }

        // 2. Calcul de l'ancienneté
        $refDate = $employe->getDatePriseService() ?? $employe->getDateEmbauche();
        $ancienneteAnnees = 0;
        $ancienneteMois = 0;
        $ancienneteLabel = "Non renseignée";
        if ($refDate) {
            $diff = $refDate->diff($now);
            $ancienneteAnnees = $diff->y;
            $ancienneteMois = $diff->m;
            if ($diff->y > 0) {
                $ancienneteLabel = sprintf('%d an%s%s', $diff->y, $diff->y > 1 ? 's' : '', $diff->m > 0 ? sprintf(' %d mois', $diff->m) : '');
            } elseif ($diff->m > 0) {
                $ancienneteLabel = sprintf('%d mois', $diff->m);
            } else {
                $ancienneteLabel = "Moins d'un mois";
            }
        }

        // 3. Libellés & Badges pour statut et situation administrative
        $statutAdminMeta = $this->getStatutAdminMeta($employe->getStatutAdministratif());
        $situationAdminMeta = $this->getSituationAdminMeta($employe->getSituationAdministrative());

        // 4. Soldes de congés
        $soldesDetails = [];
        $totalSoldeRestant = 0.0;
        foreach ($employe->getSoldesConges() as $solde) {
            $val = (float) $solde->getSolde();
            $totalSoldeRestant += $val;
            $soldesDetails[] = [
                'type' => $solde->getTypeConge() ? $solde->getTypeConge()->getNom() : 'Congé',
                'solde' => $val,
                'updatedAt' => $solde->getUpdatedAt(),
            ];
        }

        // 5. Congés
        $congesList = $employe->getConges()->toArray();
        usort($congesList, function (Conges $a, Conges $b) {
            $dateA = $a->getDateDebut() ?? new \DateTime('@0');
            $dateB = $b->getDateDebut() ?? new \DateTime('@0');
            return $dateB <=> $dateA;
        });

        $totalJoursCongesPris = 0;
        $congesApprouves = 0;
        $congesEnAttente = 0;
        $congesRefuses = 0;

        foreach ($congesList as $c) {
            if ($c->getStatut() === 1) { // 1 = Approuvé
                $congesApprouves++;
                $totalJoursCongesPris += ($c->getDateDiff() ?? 0);
            } elseif ($c->getStatut() === 0) { // 0 = En attente
                $congesEnAttente++;
            } elseif ($c->getStatut() === 2) { // 2 = Refusé
                $congesRefuses++;
            }
        }

        // 5b. Autorisations d'absence
        $absencesList = $employe->getAbsences()->toArray();
        usort($absencesList, function (\App\Entity\Absence $a, \App\Entity\Absence $b) {
            $dateA = $a->getDateDebut() ?? new \DateTime('@0');
            $dateB = $b->getDateDebut() ?? new \DateTime('@0');
            return $dateB <=> $dateA;
        });

        $totalAbsencesAccordees = 0;
        $totalAbsencesEnAttente = 0;
        $totalAbsencesRefusees = 0;
        $totalDureeAbsencesPrise = 0.0;

        foreach ($absencesList as $ab) {
            if ($ab->getStatut() === \App\Entity\Absence::STATUT_APPROUVE) {
                $totalAbsencesAccordees++;
                $totalDureeAbsencesPrise += (float) $ab->getDuree();
            } elseif ($ab->getStatut() === \App\Entity\Absence::STATUT_EN_ATTENTE) {
                $totalAbsencesEnAttente++;
            } elseif ($ab->getStatut() === \App\Entity\Absence::STATUT_REFUSE) {
                $totalAbsencesRefusees++;
            }
        }

        // 5c. Missions
        $missionsList = $this->missionRepository ? $this->missionRepository->findByEmploye($employe) : [];
        $totalMissions = count($missionsList);
        $missionsEnCours = 0;
        $missionsTerminees = 0;
        $missionsValidees = 0;

        foreach ($missionsList as $m) {
            if ($m->getStatut() === \App\Entity\Mission::STATUT_EN_COURS) {
                $missionsEnCours++;
            } elseif (in_array($m->getStatut(), [\App\Entity\Mission::STATUT_TERMINEE, \App\Entity\Mission::STATUT_CLOTUREE], true)) {
                $missionsTerminees++;
            } elseif ($m->getStatut() === \App\Entity\Mission::STATUT_VALIDEE) {
                $missionsValidees++;
            }
        }

        // 6. Pointages & Présences
        $pointagesList = $employe->getPointages()->toArray();
        usort($pointagesList, function (Pointage $a, Pointage $b) {
            $dateA = $a->getDate() ?? new \DateTime('@0');
            $dateB = $b->getDate() ?? new \DateTime('@0');
            return $dateB <=> $dateA;
        });

        $totalPointages = count($pointagesList);
        $totalPresents = 0;
        $totalAbsents = 0;
        $totalRetards = 0;

        foreach ($pointagesList as $p) {
            if ($p->isEstPresent()) {
                $totalPresents++;
            } else {
                $totalAbsents++;
            }
            if (str_contains(strtolower((string) $p->getStatus()), 'retard')) {
                $totalRetards++;
            }
        }

        $tauxPresence = $totalPointages > 0 ? (int) round(($totalPresents / $totalPointages) * 100) : 100;
        $dernierPointage = $pointagesList[0] ?? null;

        // 7. Visites médicales
        $visitesList = $employe->getVisitesProgrammees()->toArray();

        // 8. Hiérarchie structure & service
        $hierarchie = $this->buildHierarchyInfo($employe);

        // 8b. Actes administratifs concernant l'agent
        $actesList = $this->acteRepository ? $this->acteRepository->findByEmploye($employe) : [];

        // 9. Timeline d'événements
        $timeline = $this->buildTimeline($employe, $congesList, $visitesList, $pointagesList, $actesList, $absencesList, $missionsList);

        // 10. Score de complétude du dossier (0 à 100%)
        $completion = $this->calculateProfileCompletion($employe);

        return [
            'employe' => $employe,
            'age' => $age,
            'anciennete' => [
                'annees' => $ancienneteAnnees,
                'mois' => $ancienneteMois,
                'label' => $ancienneteLabel,
            ],
            'statutAdmin' => $statutAdminMeta,
            'situationAdmin' => $situationAdminMeta,
            'soldes' => [
                'total' => $totalSoldeRestant,
                'details' => $soldesDetails,
            ],
            'conges' => [
                'totalPris' => $totalJoursCongesPris,
                'approuves' => $congesApprouves,
                'enAttente' => $congesEnAttente,
                'refuses' => $congesRefuses,
                'recents' => array_slice($congesList, 0, 8),
            ],
            'absences' => [
                'total' => count($absencesList),
                'accordees' => $totalAbsencesAccordees,
                'enAttente' => $totalAbsencesEnAttente,
                'refusees' => $totalAbsencesRefusees,
                'totalDuree' => $totalDureeAbsencesPrise,
                'recents' => array_slice($absencesList, 0, 8),
            ],
            'missions' => [
                'total' => $totalMissions,
                'enCours' => $missionsEnCours,
                'validees' => $missionsValidees,
                'terminees' => $missionsTerminees,
                'recents' => array_slice($missionsList, 0, 8),
            ],
            'pointages' => [
                'total' => $totalPointages,
                'presents' => $totalPresents,
                'absents' => $totalAbsents,
                'retards' => $totalRetards,
                'tauxPresence' => $tauxPresence,
                'dernier' => $dernierPointage,
                'recents' => array_slice($pointagesList, 0, 10),
            ],
            'visites' => array_slice($visitesList, 0, 5),
            'hierarchie' => $hierarchie,
            'actes' => $actesList,
            'timeline' => $timeline,
            'completion' => $completion,
        ];
    }

    private function getStatutAdminMeta(?string $statut): array
    {
        return match ($statut) {
            Employe::STATUT_ADMIN_FONCTIONNAIRE => [
                'code' => $statut,
                'label' => 'Fonctionnaire',
                'badge' => 'bg-primary',
                'icon' => 'fa-landmark',
            ],
            Employe::STATUT_ADMIN_CONTRACTUEL => [
                'code' => $statut,
                'label' => 'Contractuel',
                'badge' => 'bg-info',
                'icon' => 'fa-file-signature',
            ],
            Employe::STATUT_ADMIN_STAGIAIRE => [
                'code' => $statut,
                'label' => 'Stagiaire',
                'badge' => 'bg-warning text-dark',
                'icon' => 'fa-user-graduate',
            ],
            Employe::STATUT_ADMIN_DETACHE => [
                'code' => $statut,
                'label' => 'Détaché',
                'badge' => 'bg-secondary',
                'icon' => 'fa-external-link-alt',
            ],
            default => [
                'code' => $statut ?? 'AUTRE',
                'label' => $statut ?? 'Non défini',
                'badge' => 'bg-dark',
                'icon' => 'fa-user-tag',
            ],
        };
    }

    private function getSituationAdminMeta(?string $situation): array
    {
        return match ($situation) {
            Employe::SITUATION_EN_ACTIVITE => [
                'code' => $situation,
                'label' => 'En activité',
                'badge' => 'bg-success',
                'icon' => 'fa-check-circle',
            ],
            Employe::SITUATION_DETACHEMENT => [
                'code' => $situation,
                'label' => 'En détachement',
                'badge' => 'bg-info',
                'icon' => 'fa-share-square',
            ],
            Employe::SITUATION_DISPONIBILITE => [
                'code' => $situation,
                'label' => 'En disponibilité',
                'badge' => 'bg-warning text-dark',
                'icon' => 'fa-pause-circle',
            ],
            Employe::SITUATION_SUSPENSION => [
                'code' => $situation,
                'label' => 'Suspendu',
                'badge' => 'bg-danger',
                'icon' => 'fa-ban',
            ],
            Employe::SITUATION_RETRAITE => [
                'code' => $situation,
                'label' => 'À la retraite',
                'badge' => 'bg-secondary',
                'icon' => 'fa-chair',
            ],
            Employe::SITUATION_RADIE => [
                'code' => $situation,
                'label' => 'Radié',
                'badge' => 'bg-danger',
                'icon' => 'fa-user-slash',
            ],
            default => [
                'code' => $situation ?? 'NON_DEFINI',
                'label' => 'En activité',
                'badge' => 'bg-success',
                'icon' => 'fa-check-circle',
            ],
        };
    }

    private function buildHierarchyInfo(Employe $employe): array
    {
        $info = [
            'institution' => $employe->getInstitution(),
            'structure' => $employe->getStructure(),
            'service' => $employe->getService(),
            'structurePath' => [],
        ];

        if ($employe->getStructure()) {
            $curr = $employe->getStructure();
            $path = [];
            while ($curr !== null) {
                array_unshift($path, $curr);
                $curr = $curr->getParent();
            }
            $info['structurePath'] = $path;
        }

        return $info;
    }

    private function buildTimeline(Employe $employe, array $congesList, array $visitesList, array $pointagesList, array $actesList = [], array $absencesList = [], array $missionsList = []): array
    {
        $events = [];

        // Actes administratifs officiels
        foreach ($actesList as $acte) {
            if ($acte->getDateSignature()) {
                $events[] = [
                    'date' => $acte->getDateSignature(),
                    'title' => sprintf('%s N° %s', $acte->getTypeActe()?->getLibelle() ?? 'Acte', $acte->getNumero()),
                    'description' => sprintf('%s (Prise d\'effet: %s)', $acte->getObjet(), $acte->getDateEffet()?->format('d/m/Y') ?? 'immédiate'),
                    'icon' => 'fa-stamp',
                    'badge' => 'bg-' . $acte->getStatutBadgeClass(),
                    'type' => 'acte',
                ];
            }
        }

        // Événement d'embauche
        if ($employe->getDateEmbauche()) {
            $events[] = [
                'date' => $employe->getDateEmbauche(),
                'title' => 'Recrutement / Date d\'embauche',
                'description' => sprintf('Intégration au sein de %s en qualité de %s', $employe->getInstitution()?->getNom() ?? 'l\'organisation', $employe->getFonction()?->getLibelle() ?? 'agent'),
                'icon' => 'fa-id-card',
                'badge' => 'bg-primary',
                'type' => 'embauche',
            ];
        }

        // Événement de prise de service
        if ($employe->getDatePriseService()) {
            $events[] = [
                'date' => $employe->getDatePriseService(),
                'title' => 'Prise effective de service',
                'description' => sprintf('Affectation à la structure %s', $employe->getStructure()?->getNom() ?? ($employe->getService()?->getNom() ?? 'centrale')),
                'icon' => 'fa-door-open',
                'badge' => 'bg-info',
                'type' => 'prise_service',
            ];
        }

        // Congés récents dans la timeline
        foreach (array_slice($congesList, 0, 5) as $conge) {
            if ($conge->getDateDebut()) {
                $statutLabel = match ($conge->getStatut()) {
                    1 => 'Approuvé',
                    2 => 'Refusé',
                    default => 'En attente',
                };
                $badge = match ($conge->getStatut()) {
                    1 => 'bg-success',
                    2 => 'bg-danger',
                    default => 'bg-warning text-dark',
                };
                $events[] = [
                    'date' => $conge->getDateDebut(),
                    'title' => sprintf('Congé : %s (%s jours)', $conge->getTypeConge()?->getNom() ?? 'Standard', $conge->getDateDiff() ?? 1),
                    'description' => sprintf('Statut: %s. Du %s au %s. %s', $statutLabel, $conge->getDateDebut()->format('d/m/Y'), $conge->getDateFin() ? $conge->getDateFin()->format('d/m/Y') : 'N/C', $conge->getMotif() ? 'Motif: ' . substr($conge->getMotif(), 0, 60) : ''),
                    'icon' => 'fa-plane-departure',
                    'badge' => $badge,
                    'type' => 'conge',
                ];
            }
        }

        // Autorisations d'absence dans la timeline
        foreach (array_slice($absencesList, 0, 5) as $absence) {
            if ($absence->getDateDebut()) {
                $events[] = [
                    'date' => $absence->getDateDebut(),
                    'title' => sprintf('Absence : %s (%s %s)', $absence->getTypeAbsence()?->getLibelle() ?? 'Autorisation', $absence->getDuree(), strtolower($absence->getUnite())),
                    'description' => sprintf('Statut: %s. Du %s au %s. %s', $absence->getStatutLabel(), $absence->getDateDebut()->format('d/m/Y'), $absence->getDateFin() ? $absence->getDateFin()->format('d/m/Y') : 'N/C', $absence->getMotif() ? 'Motif: ' . substr($absence->getMotif(), 0, 60) : ''),
                    'icon' => 'fa-calendar-check',
                    'badge' => $absence->getStatut() === 1 ? 'bg-success' : ($absence->getStatut() === 2 ? 'bg-danger' : 'bg-warning text-dark'),
                    'type' => 'absence',
                ];
            }
        }

        // Missions dans la timeline
        foreach (array_slice($missionsList, 0, 5) as $mission) {
            if ($mission->getDateDebut()) {
                $isChef = $mission->getChefMission()?->getId() === $employe->getId();
                $roleLabel = $isChef ? 'Chef de mission' : 'Participant';
                $badge = match ($mission->getStatut()) {
                    \App\Entity\Mission::STATUT_VALIDEE => 'bg-info',
                    \App\Entity\Mission::STATUT_EN_COURS => 'bg-primary',
                    \App\Entity\Mission::STATUT_TERMINEE, \App\Entity\Mission::STATUT_CLOTUREE => 'bg-success',
                    \App\Entity\Mission::STATUT_REFUSEE, \App\Entity\Mission::STATUT_ANNULEE => 'bg-danger',
                    default => 'bg-warning text-dark',
                };
                $events[] = [
                    'date' => $mission->getDateDebut(),
                    'title' => sprintf('Mission : %s (%s)', $mission->getObjet(), $mission->getDestination()),
                    'description' => sprintf('Statut: %s. Du %s au %s. Rôle: %s (Réf: %s)', $mission->getStatutBadge()['label'], $mission->getDateDebut()->format('d/m/Y'), $mission->getDateFin() ? $mission->getDateFin()->format('d/m/Y') : 'N/C', $roleLabel, $mission->getReference() ?? 'N/A'),
                    'icon' => 'fa-map-marked-alt',
                    'badge' => $badge,
                    'type' => 'mission',
                ];
            }
        }

        // Visites
        foreach (array_slice($visitesList, 0, 3) as $visite) {
            $date = method_exists($visite, 'getArrivee') ? $visite->getArrivee() : (method_exists($visite, 'getDate') ? $visite->getDate() : null);
            if ($date) {
                $visiteur = method_exists($visite, 'getVisiteur') ? $visite->getVisiteur() : 'Visiteur';
                $objet = method_exists($visite, 'getObjet') ? $visite->getObjet() : 'Rendez-vous';
                $events[] = [
                    'date' => $date,
                    'title' => sprintf('Visite : %s', $visiteur),
                    'description' => sprintf('Objet : %s', $objet ?? 'Non précisé'),
                    'icon' => 'fa-handshake',
                    'badge' => 'bg-secondary',
                    'type' => 'visite',
                ];
            }
        }

        // Trier par date décroissante
        usort($events, function ($a, $b) {
            $dateA = $a['date'] instanceof \DateTimeInterface ? $a['date'] : new \DateTime('@0');
            $dateB = $b['date'] instanceof \DateTimeInterface ? $b['date'] : new \DateTime('@0');
            return $dateB <=> $dateA;
        });

        return $events;
    }

    private function calculateProfileCompletion(Employe $employe): array
    {
        $criteria = [
            'État civil (Nom & Prénom)' => !empty($employe->getNom()) && !empty($employe->getPrenom()),
            'Date de naissance' => $employe->getDateNaissance() !== null,
            'Lieu de naissance & Sexe' => !empty($employe->getLieuNaissance()) || !empty($employe->getSexe()),
            'Coordonnées (Email & Téléphone)' => !empty($employe->getEmail()) && !empty($employe->getNumeroTelephone()),
            'Adresse de résidence' => !empty($employe->getAdresse()),
            'Matricule & Identifiant' => !empty($employe->getMatricule()),
            'Structure de rattachement' => $employe->getStructure() !== null || $employe->getService() !== null,
            'Fonction assignée' => $employe->getFonction() !== null,
            'Statut administratif & Carrière' => !empty($employe->getStatutAdministratif()),
            'Date d\'embauche / Prise de service' => $employe->getDateEmbauche() !== null || $employe->getDatePriseService() !== null,
        ];

        $totalCriteria = count($criteria);
        $completedCriteria = count(array_filter($criteria));
        $score = (int) round(($completedCriteria / $totalCriteria) * 100);

        return [
            'score' => $score,
            'criteria' => $criteria,
            'completed' => $completedCriteria,
            'total' => $totalCriteria,
        ];
    }
}
