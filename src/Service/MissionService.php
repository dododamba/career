<?php

namespace App\Service;

use App\Entity\Absence;
use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Mission;
use App\Entity\MissionLog;
use App\Entity\MissionParticipant;
use App\Entity\TypeActe;
use App\Entity\User;
use App\Repository\AbsenceRepository;
use App\Repository\CongesRepository;
use App\Repository\MissionLogRepository;
use App\Repository\MissionParticipantRepository;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MissionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MissionRepository $missionRepository,
        private readonly MissionParticipantRepository $participantRepository,
        private readonly MissionLogRepository $logRepository,
        private readonly CongesRepository $congesRepository,
        private readonly AbsenceRepository $absenceRepository,
        private readonly ?ActeService $acteService = null,
        private readonly ?SecurityAuditService $auditService = null
    ) {
    }

    /**
     * Crée une nouvelle mission avec ses participants et contrôles de disponibilité.
     */
    public function creerMission(
        Institution $institution,
        string $objet,
        string $destination,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?string $description = null,
        string $pays = 'Côte d\'Ivoire',
        ?string $lieuPrecis = null,
        ?string $itineraire = null,
        string $modeTransport = Mission::TRANSPORT_VEHICULE_SERVICE,
        ?string $budgetEstime = null,
        ?string $imputationBudgetaire = null,
        string $priseEnCharge = Mission::PRISE_EN_CHARGE_TOTAL_INSTITUTION,
        ?Employe $chefMission = null,
        array $participantsData = [],
        bool $soumettreDirectement = false,
        ?User $demandeur = null,
        ?string $reference = null
    ): Mission {
        // 1. Validation des dates
        if ($dateDebut > $dateFin) {
            throw new \LogicException('La date de début ne peut pas être postérieure à la date de fin.');
        }

        // Calcul de la durée en jours
        $diff = $dateDebut->diff($dateFin);
        $dureeJours = (int) $diff->days + 1;

        // 2. Contrôle anti-IDOR sur le chef de mission
        if ($chefMission !== null && $chefMission->getInstitution() && $chefMission->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Le chef de mission n\'appartient pas à cette institution.');
        }

        // 3. Vérification de disponibilité du chef de mission
        if ($chefMission !== null) {
            $this->verifierDisponibiliteAgent($chefMission, $dateDebut, $dateFin);
        }

        // 4. Génération de référence unique si non fournie
        $reference = $reference ?? $this->genererReference($institution, $dateDebut);

        // 5. Instanciation de la Mission
        $mission = new Mission();
        $mission->setInstitution($institution)
            ->setReference($reference)
            ->setObjet(trim($objet))
            ->setDescription($description ? trim($description) : null)
            ->setDestination(trim($destination))
            ->setPays(trim($pays))
            ->setLieuPrecis($lieuPrecis ? trim($lieuPrecis) : null)
            ->setItineraire($itineraire ? trim($itineraire) : null)
            ->setModeTransport($modeTransport)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setDureeJours($dureeJours)
            ->setBudgetEstime($budgetEstime !== null && $budgetEstime !== '' ? (string) $budgetEstime : null)
            ->setImputationBudgetaire($imputationBudgetaire ? trim($imputationBudgetaire) : null)
            ->setPriseEnCharge($priseEnCharge)
            ->setChefMission($chefMission)
            ->setStatut($soumettreDirectement ? Mission::STATUT_SOUMISE : Mission::STATUT_BROUILLON)
            ->setCreePar($demandeur)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($mission);

        // 6. Ajout automatique du chef de mission comme participant
        if ($chefMission !== null) {
            $participantChef = new MissionParticipant();
            $participantChef->setMission($mission)
                ->setEmploye($chefMission)
                ->setRole(MissionParticipant::ROLE_CHEF_MISSION);
            $mission->addParticipant($participantChef);
            $this->entityManager->persist($participantChef);
        }

        // 7. Traitement des autres participants
        $agentIdsTraites = $chefMission ? [$chefMission->getId()] : [];

        foreach ($participantsData as $data) {
            /** @var Employe $employe */
            $employe = $data['employe'] ?? null;
            if (!$employe instanceof Employe) {
                continue;
            }

            if (in_array($employe->getId(), $agentIdsTraites, true)) {
                continue; // Éviter les doublons
            }

            // Anti-IDOR
            if ($employe->getInstitution() && $employe->getInstitution()->getId() !== $institution->getId()) {
                throw new AccessDeniedException(sprintf('L\'agent %s n\'appartient pas à cette institution.', $employe->getNomComplet()));
            }

            // Disponibilité
            $this->verifierDisponibiliteAgent($employe, $dateDebut, $dateFin);

            $indemnite = isset($data['indemnite']) ? (string) $data['indemnite'] : (isset($data['indemnitesMission']) ? (string) $data['indemnitesMission'] : null);
            $transport = isset($data['transport']) ? (string) $data['transport'] : (isset($data['fraisTransport']) ? (string) $data['fraisTransport'] : null);
            $total = isset($data['total']) ? (string) $data['total'] : (isset($data['totalFrais']) ? (string) $data['totalFrais'] : null);
            if ($total === null && ($indemnite !== null || $transport !== null)) {
                $total = (string) (((float) ($indemnite ?? 0)) + ((float) ($transport ?? 0)));
            }

            $part = new MissionParticipant();
            $part->setMission($mission)
                ->setEmploye($employe)
                ->setRole($data['role'] ?? MissionParticipant::ROLE_PARTICIPANT)
                ->setIndemniteJournaliere($indemnite)
                ->setFraisTransport($transport)
                ->setTotalFrais($total);

            $mission->addParticipant($part);
            $this->entityManager->persist($part);
            $agentIdsTraites[] = $employe->getId();
        }

        // 8. Journalisation
        $actionLabel = $soumettreDirectement ? 'Mission créée et soumise pour validation' : 'Mission créée (Brouillon)';
        $this->creerLog(
            $mission,
            $soumettreDirectement ? 'submit' : 'create',
            $actionLabel,
            sprintf('Mission à destination de %s pour %d agent(s) du %s au %s', $destination, count($agentIdsTraites), $dateDebut->format('d/m/Y'), $dateFin->format('d/m/Y')),
            $demandeur
        );

        $this->auditService?->log(
            'MISSION_CREATE',
            'Création de mission',
            $demandeur?->getUserIdentifier(),
            $demandeur,
            sprintf('Mission %s créée pour %s (%s)', $reference, $destination, $objet)
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Modifie une mission existante avant validation.
     */
    public function modifierMission(
        Mission $mission,
        string $objet,
        string $destination,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?string $description = null,
        string $pays = 'Côte d\'Ivoire',
        ?string $lieuPrecis = null,
        ?string $itineraire = null,
        string $modeTransport = Mission::TRANSPORT_VEHICULE_SERVICE,
        ?string $budgetEstime = null,
        ?string $imputationBudgetaire = null,
        string $priseEnCharge = Mission::PRISE_EN_CHARGE_TOTAL_INSTITUTION,
        ?Employe $chefMission = null,
        ?User $modificateur = null
    ): Mission {
        if (!in_array($mission->getStatut(), [Mission::STATUT_BROUILLON, Mission::STATUT_SOUMISE], true)) {
            throw new \LogicException('Une mission validée ou en cours d\'exécution ne peut pas être modifiée.');
        }

        if ($dateDebut > $dateFin) {
            throw new \LogicException('La date de début ne peut pas être postérieure à la date de fin.');
        }

        $institution = $mission->getInstitution();
        if ($chefMission !== null && $chefMission->getInstitution() && $chefMission->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Le chef de mission n\'appartient pas à cette institution.');
        }

        // Vérification disponibilité chefMission
        if ($chefMission !== null) {
            $this->verifierDisponibiliteAgent($chefMission, $dateDebut, $dateFin, $mission->getId());
        }

        // Vérification disponibilité de tous les participants existants
        foreach ($mission->getParticipants() as $participant) {
            if ($participant->getEmploye()) {
                $this->verifierDisponibiliteAgent($participant->getEmploye(), $dateDebut, $dateFin, $mission->getId());
            }
        }

        $diff = $dateDebut->diff($dateFin);
        $dureeJours = (int) $diff->days + 1;

        $mission->setObjet(trim($objet))
            ->setDescription($description ? trim($description) : null)
            ->setDestination(trim($destination))
            ->setPays(trim($pays))
            ->setLieuPrecis($lieuPrecis ? trim($lieuPrecis) : null)
            ->setItineraire($itineraire ? trim($itineraire) : null)
            ->setModeTransport($modeTransport)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setDureeJours($dureeJours)
            ->setBudgetEstime($budgetEstime !== null && $budgetEstime !== '' ? (string) $budgetEstime : null)
            ->setImputationBudgetaire($imputationBudgetaire ? trim($imputationBudgetaire) : null)
            ->setPriseEnCharge($priseEnCharge)
            ->setChefMission($chefMission)
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $mission,
            'update',
            'Mission modifiée',
            sprintf('Paramètres mis à jour : %s du %s au %s', $destination, $dateDebut->format('d/m/Y'), $dateFin->format('d/m/Y')),
            $modificateur
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Soumet une mission brouillon pour validation hiérarchique.
     */
    public function soumettreMission(Mission $mission, ?User $utilisateur = null): Mission
    {
        if ($mission->getStatut() !== Mission::STATUT_BROUILLON) {
            throw new \LogicException('Seule une mission en brouillon peut être soumise pour validation.');
        }

        if ($mission->getParticipants()->count() === 0 && $mission->getChefMission() === null) {
            throw new \LogicException('Une mission doit comporter au moins un agent participant ou chef de mission.');
        }

        $mission->setStatut(Mission::STATUT_SOUMISE);
        $mission->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $mission,
            'submit',
            'Mission soumise pour validation',
            sprintf('Mission soumise pour %s à destination de %s', $mission->getObjet(), $mission->getDestination()),
            $utilisateur
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Valide la mission et génère automatiquement l'Ordre de Mission officiel (Acte administratif).
     */
    public function validerMission(
        Mission $mission,
        User $validateur,
        bool $genererOrdreMission = true,
        ?string $numeroOrdre = null,
        ?string $commentaire = null
    ): Mission {
        if (!in_array($mission->getStatut(), [Mission::STATUT_SOUMISE, Mission::STATUT_BROUILLON], true)) {
            throw new \LogicException('Seule une mission en attente de validation peut être validée.');
        }

        // Règle d'anti-auto-validation
        $this->verifierNonParticipationValidateur($mission, $validateur);

        $mission->setStatut(Mission::STATUT_VALIDEE);
        $mission->setValidePar($validateur);
        $mission->setDateValidation(new \DateTime());
        $mission->setCommentaireValidation($commentaire ? trim($commentaire) : null);
        $mission->setUpdatedAt(new \DateTimeImmutable());

        // Génération automatique de l'Acte officiel Ordre de Mission
        if ($genererOrdreMission && $this->acteService !== null) {
            $acte = $this->genererOrdreMissionOfficiel($mission, $validateur, $numeroOrdre);
            if ($acte !== null) {
                $mission->setActe($acte);
            }
        }

        $this->creerLog(
            $mission,
            'validate',
            'Mission validée & Ordre de mission émis',
            sprintf('Validée par %s le %s', $validateur->getNomComplet() ?? $validateur->getUserIdentifier(), (new \DateTime())->format('d/m/Y H:i')),
            $validateur
        );

        $this->auditService?->log(
            'MISSION_VALIDATE',
            'Validation de mission',
            $validateur->getUserIdentifier(),
            $validateur,
            sprintf('Mission %s validée à destination de %s', $mission->getReference(), $mission->getDestination())
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Marque la mission comme étant en cours d'exécution.
     */
    public function demarrerMission(Mission $mission, ?User $utilisateur = null): Mission
    {
        if ($mission->getStatut() !== Mission::STATUT_VALIDEE) {
            throw new \LogicException('Seule une mission validée peut être marquée en cours d\'exécution.');
        }

        $mission->setStatut(Mission::STATUT_EN_COURS);
        $mission->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $mission,
            'start',
            'Mission en cours d\'exécution',
            'Départ en mission effectif',
            $utilisateur
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Termine l'exécution de la mission et attend le rapport.
     */
    public function terminerMission(Mission $mission, ?User $utilisateur = null): Mission
    {
        if (!in_array($mission->getStatut(), [Mission::STATUT_VALIDEE, Mission::STATUT_EN_COURS], true)) {
            throw new \LogicException('Seule une mission validée ou en cours peut être terminée.');
        }

        $mission->setStatut(Mission::STATUT_TERMINEE);
        $mission->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $mission,
            'complete',
            'Mission terminée (en attente du rapport)',
            'Retour de mission des agents',
            $utilisateur
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Enregistre le compte-rendu ou rapport de fin de mission.
     */
    public function soumettreRapport(
        Mission $mission,
        string $rapportTexte,
        ?Document $documentRapport = null,
        ?User $redacteur = null
    ): Mission {
        if (!in_array($mission->getStatut(), [Mission::STATUT_VALIDEE, Mission::STATUT_EN_COURS, Mission::STATUT_TERMINEE], true)) {
            throw new \LogicException('Le rapport ne peut être déposé que pour une mission en cours ou achevée.');
        }

        if (trim($rapportTexte) === '' && $documentRapport === null) {
            throw new \LogicException('Veuillez fournir un compte-rendu rédigé ou un document de rapport.');
        }

        // Anti-IDOR sur le document
        if ($documentRapport !== null && $documentRapport->getInstitution() && $documentRapport->getInstitution()->getId() !== $mission->getInstitution()->getId()) {
            throw new AccessDeniedException('Le document de rapport n\'appartient pas à la même institution.');
        }

        $mission->setRapportTexte(trim($rapportTexte))
            ->setDocumentRapport($documentRapport)
            ->setDateRapport(new \DateTime())
            ->setStatut(Mission::STATUT_TERMINEE)
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $mission,
            'submit_report',
            'Rapport de mission déposé',
            sprintf('Rapport rédigé par %s', $redacteur ? ($redacteur->getNomComplet() ?? $redacteur->getUserIdentifier()) : 'l\'équipe de mission'),
            $redacteur
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Valide le rapport de mission et clôture définitivement la mission.
     */
    public function validerRapportEtCloturer(
        Mission $mission,
        User $validateur,
        ?string $commentaire = null
    ): Mission {
        if ($mission->getStatut() !== Mission::STATUT_TERMINEE) {
            throw new \LogicException('Seule une mission terminée avec rapport déposé peut être clôturée.');
        }

        if (empty($mission->getRapportTexte()) && $mission->getDocumentRapport() === null) {
            throw new \LogicException('Impossible de clôturer une mission sans rapport ou compte-rendu validé.');
        }

        $this->verifierNonParticipationValidateur($mission, $validateur);

        $mission->setStatut(Mission::STATUT_CLOTUREE);
        $mission->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $mission,
            'close',
            'Rapport validé & Mission clôturée',
            sprintf('Clôturée par %s%s', $validateur->getNomComplet() ?? $validateur->getUserIdentifier(), $commentaire ? (' (Observations: ' . trim($commentaire) . ')') : ''),
            $validateur
        );

        $this->auditService?->log(
            'MISSION_CLOSE',
            'Clôture de mission',
            $validateur->getUserIdentifier(),
            $validateur,
            sprintf('Mission %s clôturée avec succès', $mission->getReference())
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Refuse une mission avec motif obligatoire.
     */
    public function rejeterMission(Mission $mission, User $refuseur, string $motifRefus): Mission
    {
        if ($mission->getStatut() !== Mission::STATUT_SOUMISE) {
            throw new \LogicException('Seule une mission soumise pour validation peut être refusée.');
        }

        if (trim($motifRefus) === '') {
            throw new \LogicException('Le motif du refus est obligatoire.');
        }

        $this->verifierNonParticipationValidateur($mission, $refuseur);

        $mission->setStatut(Mission::STATUT_REFUSEE);
        $mission->setCommentaireValidation(trim($motifRefus));
        $mission->setDateValidation(new \DateTime());
        $mission->setValidePar($refuseur);
        $mission->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $mission,
            'reject',
            'Mission refusée',
            sprintf('Refusée par %s. Motif : %s', $refuseur->getNomComplet() ?? $refuseur->getUserIdentifier(), trim($motifRefus)),
            $refuseur
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Annule une mission et abroge l'ordre de mission s'il avait été émis.
     */
    public function annulerMission(Mission $mission, User $utilisateur, ?string $motif = null): Mission
    {
        if (in_array($mission->getStatut(), [Mission::STATUT_CLOTUREE, Mission::STATUT_ANNULEE, Mission::STATUT_REFUSEE], true)) {
            throw new \LogicException('Une mission déjà clôturée, refusée ou annulée ne peut pas être annulée.');
        }

        // Abrogation de l'acte officiel d'ordre de mission le cas échéant
        if ($mission->getActe() !== null) {
            $mission->getActe()->setStatut(Acte::STATUT_ABROGE);
        }

        $mission->setStatut(Mission::STATUT_ANNULEE);
        $mission->setCommentaireValidation($motif ? ('Annulation : ' . trim($motif)) : 'Mission annulée');
        $mission->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $mission,
            'cancel',
            'Mission annulée',
            sprintf('Annulée par %s%s', $utilisateur->getNomComplet() ?? $utilisateur->getUserIdentifier(), $motif ? (' (Motif: ' . trim($motif) . ')') : ''),
            $utilisateur
        );

        $this->entityManager->flush();

        return $mission;
    }

    /**
     * Supprime définitivement une mission (brouillon, annulée ou refusée uniquement).
     */
    public function supprimerMission(Mission $mission, User $utilisateur): void
    {
        if (in_array($mission->getStatut(), [Mission::STATUT_VALIDEE, Mission::STATUT_EN_COURS, Mission::STATUT_TERMINEE, Mission::STATUT_CLOTUREE], true)) {
            throw new \LogicException('Impossible de supprimer une mission validée ou exécutée. Veuillez l\'annuler si nécessaire.');
        }

        $this->auditService?->log(
            'MISSION_DELETE',
            'Suppression de mission',
            $utilisateur->getUserIdentifier(),
            $utilisateur,
            sprintf('Mission %s supprimée', $mission->getReference())
        );

        $this->entityManager->remove($mission);
        $this->entityManager->flush();
    }

    /**
     * Vérifie la disponibilité d'un agent pour une période donnée.
     */
    public function verifierDisponibiliteAgent(
        Employe $employe,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeMissionId = null
    ): void {
        // 1. Vérification congés en cours / approuvés / en attente
        $qbConges = $this->congesRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.employe = :employe')
            ->andWhere('c.statut IN (:statuts)')
            ->andWhere('c.dateDebut <= :dateFin')
            ->andWhere('c.dateFin >= :dateDebut')
            ->setParameter('employe', $employe)
            ->setParameter('statuts', [Conges::STATUT_EN_ATTENTE, Conges::STATUT_APPROUVE])
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'));

        if ((int) $qbConges->getQuery()->getSingleScalarResult() > 0) {
            throw new \LogicException(sprintf(
                'L\'agent %s est indisponible sur cette période : un congé est déjà programmé ou en attente.',
                $employe->getNomComplet()
            ));
        }

        // 2. Vérification autorisations d'absence
        $overlappingAbsences = $this->absenceRepository->findOverlapping($employe, $dateDebut, $dateFin);
        if (!empty($overlappingAbsences)) {
            throw new \LogicException(sprintf(
                'L\'agent %s est indisponible sur cette période : une autorisation d\'absence est déjà enregistrée.',
                $employe->getNomComplet()
            ));
        }

        // 3. Vérification autre mission simultanée
        $overlappingMissions = $this->missionRepository->findOverlappingForEmploye($employe, $dateDebut, $dateFin, $excludeMissionId);
        if (!empty($overlappingMissions)) {
            throw new \LogicException(sprintf(
                'L\'agent %s est déjà engagé sur une autre mission active sur cette période (%s).',
                $employe->getNomComplet(),
                $overlappingMissions[0]->getReference()
            ));
        }
    }

    /**
     * Règle de séparation des fonctions : le validateur ne doit pas faire partie de la mission.
     */
    private function verifierNonParticipationValidateur(Mission $mission, User $validateur): void
    {
        $employeValidateur = $validateur->getEmploye();

        if ($employeValidateur !== null && $mission->hasParticipant($employeValidateur)) {
            throw new AccessDeniedException('Vous ne pouvez pas valider ou clôturer une mission dont vous faites partie.');
        }

        // Vérification par association User -> Employe
        if ($mission->getChefMission() && $mission->getChefMission()->getUser() && $mission->getChefMission()->getUser()->getId() === $validateur->getId()) {
            throw new AccessDeniedException('Vous ne pouvez pas valider ou clôturer votre propre mission.');
        }

        foreach ($mission->getParticipants() as $participant) {
            $emp = $participant->getEmploye();
            if ($emp && $emp->getUser() && $emp->getUser()->getId() === $validateur->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas valider ou clôturer une mission dont vous faites partie.');
            }
        }
    }

    /**
     * Génère l'acte administratif officiel d'ordre de mission.
     */
    private function genererOrdreMissionOfficiel(Mission $mission, User $validateur, ?string $numeroOrdre = null): ?Acte
    {
        try {
            $institution = $mission->getInstitution();
            $typeActeRepository = $this->entityManager->getRepository(TypeActe::class);
            $typeActe = $typeActeRepository->findOneBy(['code' => TypeActe::CODE_ORDRE_MISSION])
                ?? $typeActeRepository->findOneBy(['code' => TypeActe::CODE_DECISION])
                ?? $typeActeRepository->findOneBy([]);

            if (!$typeActe) {
                return null;
            }

            $numero = $numeroOrdre ?? sprintf('OM-%s-%04d', $mission->getDateDebut()->format('Y'), $mission->getId() ?? rand(1000, 9999));
            $agentPrincipal = $mission->getChefMission() ?? $mission->getParticipants()->first()?->getEmploye();

            return $this->acteService->creerActe(
                institution: $institution,
                typeActe: $typeActe,
                numero: $numero,
                dateSignature: new \DateTime(),
                dateEffet: $mission->getDateDebut(),
                objet: sprintf(
                    'Ordre de mission : %s (Destination : %s du %s au %s)',
                    $mission->getObjet(),
                    $mission->getDestination(),
                    $mission->getDateDebut()->format('d/m/Y'),
                    $mission->getDateFin()->format('d/m/Y')
                ),
                signataireQualite: 'L\'Autorité Compétente',
                signataireNom: $validateur->getNomComplet(),
                statut: Acte::STATUT_SIGNE,
                datePublication: new \DateTime(),
                contenuResume: sprintf(
                    'Mission officielle à destination de %s (%s). Durée : %d jour(s). Moyen de transport : %s. Prise en charge : %s.',
                    $mission->getDestination(),
                    $mission->getPays(),
                    $mission->getDureeJours(),
                    $mission->getModeTransport(),
                    $mission->getPriseEnCharge()
                ),
                employe: $agentPrincipal,
                currentUser: $validateur
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function genererReference(Institution $institution, \DateTimeInterface $date): string
    {
        $annee = $date->format('Y');
        $random = rand(1000, 9999);

        return sprintf('MIS-%s-%04d', $annee, $random);
    }

    private function creerLog(
        Mission $mission,
        string $action,
        string $actionLabel,
        ?string $description,
        ?User $user
    ): MissionLog {
        if ($user === null) {
            $user = $mission->getCreePar() ?? $this->entityManager->getRepository(User::class)->findOneBy([]) ?? new User();
        }

        $log = new MissionLog();
        $log->setAction($action)
            ->setActionLabel($actionLabel)
            ->setDescription($description)
            ->setUser($user)
            ->setCreatedAt(new \DateTime());

        $mission->addMissionLog($log);
        $this->entityManager->persist($log);

        return $log;
    }
}
