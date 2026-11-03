<?php

namespace App\Service;

use App\Entity\Absence;
use App\Entity\AbsenceLog;
use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\TypeAbsence;
use App\Entity\TypeActe;
use App\Entity\User;
use App\Repository\AbsenceLogRepository;
use App\Repository\AbsenceRepository;
use App\Repository\CongesRepository;
use App\Repository\TypeAbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AbsenceService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AbsenceRepository $absenceRepository,
        private readonly TypeAbsenceRepository $typeAbsenceRepository,
        private readonly CongesRepository $congesRepository,
        private readonly ?ActeService $acteService = null,
        private readonly ?SecurityAuditService $auditService = null
    ) {
    }

    /**
     * Crée et soumet une nouvelle demande d'autorisation d'absence avec contrôles complets.
     */
    public function demanderAbsence(
        Employe $employe,
        TypeAbsence $typeAbsence,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        string $motif,
        ?float $duree = null,
        ?string $unite = null,
        ?\DateTimeInterface $heureDebut = null,
        ?\DateTimeInterface $heureFin = null,
        ?Document $document = null,
        ?User $demandeur = null
    ): Absence {
        $institution = $employe->getInstitution();
        if (!$institution) {
            throw new \LogicException('L\'employé doit être rattaché à une institution.');
        }

        // 1. Contrôle anti-IDOR sur le type d'absence
        if ($typeAbsence->getInstitution() !== null && $typeAbsence->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Ce type d\'absence n\'est pas accessible pour cette institution.');
        }

        // 2. Contrôle anti-IDOR sur le justificatif
        if ($document !== null && $document->getInstitution() && $document->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Le document justificatif n\'appartient pas à la même institution.');
        }

        // 3. Validation de l'ordre chronologique des dates
        if ($dateDebut > $dateFin) {
            throw new \LogicException('La date de début ne peut pas être postérieure à la date de fin.');
        }

        // 4. Validation des heures si applicables
        if ($heureDebut !== null && $heureFin !== null && $dateDebut->format('Y-m-d') === $dateFin->format('Y-m-d')) {
            if ($heureDebut >= $heureFin) {
                throw new \LogicException('L\'heure de début doit être antérieure à l\'heure de fin.');
            }
        }

        // 5. Détermination de l'unité et calcul de la durée
        $unite = $unite ?? $typeAbsence->getUnite();
        if ($duree === null || $duree <= 0) {
            $duree = $this->calculerDuree($dateDebut, $dateFin, $unite, $heureDebut, $heureFin);
        }

        // 6. Anti-chevauchement avec absences existantes et congés existants
        if ($this->verifierChevauchement($employe, $dateDebut, $dateFin)) {
            throw new \LogicException('L\'agent a déjà une demande d\'absence ou de congé en cours ou validée sur cette période.');
        }

        // 7. Contrôle du justificatif obligatoire
        if ($typeAbsence->isJustificatifRequis() && $document === null) {
            throw new \LogicException(sprintf(
                'Un document justificatif est obligatoire pour le type d\'absence "%s".',
                $typeAbsence->getLibelle()
            ));
        }

        // 8. Contrôle de la durée maximale par demande
        if ($typeAbsence->getDureeMax() !== null && (float) $duree > (float) $typeAbsence->getDureeMax()) {
            throw new \LogicException(sprintf(
                'La durée demandée (%.2f %s) dépasse la durée maximale autorisée pour "%s" (%.2f %s).',
                $duree,
                strtolower($unite),
                $typeAbsence->getLibelle(),
                (float) $typeAbsence->getDureeMax(),
                strtolower($unite)
            ));
        }

        // 9. Contrôle du quota annuel cumulé
        if ($typeAbsence->getQuotaAnnuelMax() !== null) {
            $annee = (int) $dateDebut->format('Y');
            $cumul = $this->absenceRepository->getDureeCumuleeParAnnee($employe, $typeAbsence, $annee);
            if ($cumul + $duree > (float) $typeAbsence->getQuotaAnnuelMax()) {
                throw new \LogicException(sprintf(
                    'Le quota annuel pour "%s" est dépassé : déjà pris/en attente %.2f %s, demandé %.2f %s, quota maximum %.2f %s.',
                    $typeAbsence->getLibelle(),
                    $cumul,
                    strtolower($unite),
                    $duree,
                    strtolower($unite),
                    (float) $typeAbsence->getQuotaAnnuelMax(),
                    strtolower($unite)
                ));
            }
        }

        // 10. Instanciation de l'Absence
        $absence = new Absence();
        $absence->setEmploye($employe)
            ->setTypeAbsence($typeAbsence)
            ->setInstitution($institution)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setHeureDebut($heureDebut)
            ->setHeureFin($heureFin)
            ->setDuree(number_format($duree, 2, '.', ''))
            ->setUnite($unite)
            ->setMotif(trim($motif))
            ->setDocument($document)
            ->setStatut(Absence::STATUT_EN_ATTENTE)
            ->setCreePar($demandeur)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($absence);

        // 11. Journalisation dans AbsenceLog
        $this->creerLog(
            $absence,
            'create',
            'Demande d\'absence déposée',
            sprintf('Demande de %.2f %s du %s au %s', $duree, strtolower($unite), $dateDebut->format('d/m/Y'), $dateFin->format('d/m/Y')),
            $demandeur
        );

        // 12. Security Audit Log
        $this->auditService?->log(
            'ABSENCE_REQUEST',
            'Demande d\'absence',
            $demandeur?->getUserIdentifier(),
            $demandeur,
            sprintf('Demande d\'absence #%s créée pour l\'agent %s (#%d)', $typeAbsence->getLibelle(), $employe->getNomComplet(), $employe->getId())
        );

        $this->entityManager->flush();

        return $absence;
    }

    /**
     * Modifie une demande d'absence existante en attente.
     */
    public function modifierAbsence(
        Absence $absence,
        TypeAbsence $typeAbsence,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        string $motif,
        ?float $duree = null,
        ?string $unite = null,
        ?\DateTimeInterface $heureDebut = null,
        ?\DateTimeInterface $heureFin = null,
        ?Document $document = null,
        ?User $modificateur = null
    ): Absence {
        if ($absence->getStatut() !== Absence::STATUT_EN_ATTENTE) {
            throw new \LogicException('Seule une demande d\'absence en attente peut être modifiée.');
        }

        $employe = $absence->getEmploye();
        $institution = $absence->getInstitution();

        // Contrôle anti-IDOR
        if ($typeAbsence->getInstitution() !== null && $typeAbsence->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Ce type d\'absence n\'est pas accessible pour cette institution.');
        }

        if ($dateDebut > $dateFin) {
            throw new \LogicException('La date de début ne peut pas être postérieure à la date de fin.');
        }

        $unite = $unite ?? $typeAbsence->getUnite();
        if ($duree === null || $duree <= 0) {
            $duree = $this->calculerDuree($dateDebut, $dateFin, $unite, $heureDebut, $heureFin);
        }

        // Anti-chevauchement en excluant l'absence courante
        if ($this->verifierChevauchement($employe, $dateDebut, $dateFin, $absence->getId())) {
            throw new \LogicException('L\'agent a déjà une autre demande d\'absence ou de congé sur cette période.');
        }

        if ($typeAbsence->isJustificatifRequis() && $document === null && $absence->getDocument() === null) {
            throw new \LogicException(sprintf('Un document justificatif est obligatoire pour "%s".', $typeAbsence->getLibelle()));
        }

        if ($typeAbsence->getDureeMax() !== null && (float) $duree > (float) $typeAbsence->getDureeMax()) {
            throw new \LogicException(sprintf(
                'La durée demandée (%.2f %s) dépasse la durée maximale autorisée pour "%s" (%.2f %s).',
                $duree,
                strtolower($unite),
                $typeAbsence->getLibelle(),
                (float) $typeAbsence->getDureeMax(),
                strtolower($unite)
            ));
        }

        $absence->setTypeAbsence($typeAbsence)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setHeureDebut($heureDebut)
            ->setHeureFin($heureFin)
            ->setDuree(number_format($duree, 2, '.', ''))
            ->setUnite($unite)
            ->setMotif(trim($motif))
            ->setUpdatedAt(new \DateTimeImmutable());

        if ($document !== null) {
            $absence->setDocument($document);
        }

        $this->creerLog(
            $absence,
            'update',
            'Demande d\'absence modifiée',
            sprintf('Nouvelle période : du %s au %s (%.2f %s)', $dateDebut->format('d/m/Y'), $dateFin->format('d/m/Y'), $duree, strtolower($unite)),
            $modificateur
        );

        $this->entityManager->flush();

        return $absence;
    }

    /**
     * Approuve/accorde une autorisation d'absence et génère l'acte administratif correspondant.
     */
    public function approuverAbsence(
        Absence $absence,
        User $approbateur,
        bool $genererActe = true,
        ?string $numeroActe = null,
        ?string $commentaire = null
    ): Absence {
        if ($absence->getStatut() !== Absence::STATUT_EN_ATTENTE) {
            throw new \LogicException('Seule une demande d\'absence en attente peut être approuvée.');
        }

        // Règle stricte anti-auto-approbation
        $employe = $absence->getEmploye();
        if ($employe) {
            if ($employe->getUser() && $employe->getUser()->getId() === $approbateur->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas approuver votre propre demande d\'absence.');
            }
            if ($approbateur->getEmploye() && $approbateur->getEmploye()->getId() === $employe->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas approuver votre propre demande d\'absence.');
            }
        }

        $absence->setStatut(Absence::STATUT_APPROUVE);
        $absence->setApprouvePar($approbateur);
        $absence->setDateDecision(new \DateTime());
        $absence->setCommentaireDecision($commentaire);
        $absence->setUpdatedAt(new \DateTimeImmutable());

        // Génération automatique de l'acte officiel d'autorisation d'absence
        if ($genererActe && $absence->getInstitution() !== null && $this->acteService !== null && $employe !== null) {
            $acte = $this->genererActePourAbsence($absence, $approbateur, $numeroActe);
            if ($acte !== null) {
                $absence->setActe($acte);
            }
        }

        $this->creerLog(
            $absence,
            'approve',
            'Autorisation d\'absence accordée',
            sprintf('Accordée par %s le %s', $approbateur->getNomComplet() ?? $approbateur->getUserIdentifier(), (new \DateTime())->format('d/m/Y H:i')),
            $approbateur
        );

        $this->auditService?->log(
            'ABSENCE_APPROVE',
            'Autorisation d\'absence accordée',
            $approbateur->getUserIdentifier(),
            $approbateur,
            sprintf('Absence #%d accordée pour l\'agent %s', $absence->getId(), $employe?->getNomComplet() ?? 'Agent')
        );

        $this->entityManager->flush();

        return $absence;
    }

    /**
     * Refuse une demande d'absence avec motif obligatoire.
     */
    public function rejeterAbsence(Absence $absence, User $refuseur, string $motifRefus): Absence
    {
        if ($absence->getStatut() !== Absence::STATUT_EN_ATTENTE) {
            throw new \LogicException('Seule une demande d\'absence en attente peut être refusée.');
        }

        if (trim($motifRefus) === '') {
            throw new \LogicException('Le motif du refus est obligatoire.');
        }

        $employe = $absence->getEmploye();
        if ($employe) {
            if ($employe->getUser() && $employe->getUser()->getId() === $refuseur->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas rejeter votre propre demande d\'absence.');
            }
            if ($refuseur->getEmploye() && $refuseur->getEmploye()->getId() === $employe->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas rejeter votre propre demande d\'absence.');
            }
        }

        $absence->setStatut(Absence::STATUT_REFUSE);
        $absence->setApprouvePar($refuseur);
        $absence->setDateDecision(new \DateTime());
        $absence->setCommentaireDecision(trim($motifRefus));
        $absence->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $absence,
            'reject',
            'Demande d\'absence refusée',
            sprintf('Refusée par %s. Motif : %s', $refuseur->getNomComplet() ?? $refuseur->getUserIdentifier(), trim($motifRefus)),
            $refuseur
        );

        $this->auditService?->log(
            'ABSENCE_REJECT',
            'Demande d\'absence refusée',
            $refuseur->getUserIdentifier(),
            $refuseur,
            sprintf('Absence #%d refusée pour %s. Motif : %s', $absence->getId(), $employe?->getNomComplet() ?? 'Agent', trim($motifRefus))
        );

        $this->entityManager->flush();

        return $absence;
    }

    /**
     * Annule une demande d'absence (en attente ou accordée).
     */
    public function annulerAbsence(Absence $absence, User $utilisateur, ?string $motif = null): Absence
    {
        if (!in_array($absence->getStatut(), [Absence::STATUT_EN_ATTENTE, Absence::STATUT_APPROUVE], true)) {
            throw new \LogicException('Seule une absence en attente ou accordée peut être annulée.');
        }

        // Si un acte administratif a déjà été émis, on l'abroge
        if ($absence->getActe() !== null) {
            $absence->getActe()->setStatut(Acte::STATUT_ABROGE);
        }

        $absence->setStatut(Absence::STATUT_ANNULE);
        $absence->setCommentaireDecision($motif ? ('Annulation : ' . trim($motif)) : 'Absence annulée');
        $absence->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $absence,
            'cancel',
            'Autorisation d\'absence annulée',
            sprintf('Annulée par %s%s', $utilisateur->getNomComplet() ?? $utilisateur->getUserIdentifier(), $motif ? ' (Motif: ' . trim($motif) . ')' : ''),
            $utilisateur
        );

        $this->auditService?->log(
            'ABSENCE_CANCEL',
            'Annulation d\'absence',
            $utilisateur->getUserIdentifier(),
            $utilisateur,
            sprintf('Absence #%d annulée pour %s', $absence->getId(), $absence->getEmploye()?->getNomComplet() ?? 'Agent')
        );

        $this->entityManager->flush();

        return $absence;
    }

    /**
     * Supprime définitivement une absence (uniquement en attente ou annulée).
     */
    public function supprimerAbsence(Absence $absence, User $utilisateur): void
    {
        if ($absence->getStatut() === Absence::STATUT_APPROUVE) {
            throw new \LogicException('Impossible de supprimer une autorisation d\'absence déjà accordée. Veuillez d\'abord l\'annuler.');
        }

        $this->auditService?->log(
            'ABSENCE_DELETE',
            'Suppression d\'absence',
            $utilisateur->getUserIdentifier(),
            $utilisateur,
            sprintf('Demande d\'absence #%d supprimée', $absence->getId())
        );

        $this->entityManager->remove($absence);
        $this->entityManager->flush();
    }

    /**
     * Vérifie le chevauchement avec une autre absence OU un congé actif/en attente.
     */
    public function verifierChevauchement(
        Employe $employe,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeAbsenceId = null
    ): bool {
        // 1. Chevauchement avec d'autres absences
        $overlappingAbsences = $this->absenceRepository->findOverlapping($employe, $dateDebut, $dateFin, $excludeAbsenceId);
        if (!empty($overlappingAbsences)) {
            return true;
        }

        // 2. Chevauchement avec des congés
        $qb = $this->congesRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.employe = :employe')
            ->andWhere('c.statut IN (:statuts)')
            ->andWhere('c.dateDebut <= :dateFin')
            ->andWhere('c.dateFin >= :dateDebut')
            ->setParameter('employe', $employe)
            ->setParameter('statuts', [Conges::STATUT_EN_ATTENTE, Conges::STATUT_APPROUVE])
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'));

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Calcule la durée d'une absence.
     */
    public function calculerDuree(
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        string $unite,
        ?\DateTimeInterface $heureDebut = null,
        ?\DateTimeInterface $heureFin = null
    ): float {
        if ($unite === TypeAbsence::UNITE_HEURES && $heureDebut !== null && $heureFin !== null) {
            $diffSeconds = $heureFin->getTimestamp() - $heureDebut->getTimestamp();
            if ($diffSeconds > 0) {
                return round($diffSeconds / 3600, 2);
            }
        }

        $diff = $dateDebut->diff($dateFin);

        return (float) ($diff->days + 1);
    }

    /**
     * Génère l'acte administratif officiel d'autorisation d'absence.
     */
    private function genererActePourAbsence(Absence $absence, User $approbateur, ?string $numeroActe = null): ?Acte
    {
        try {
            $institution = $absence->getInstitution();
            if (!$institution) {
                return null;
            }

            $typeActeRepository = $this->entityManager->getRepository(TypeActe::class);
            $typeActe = $typeActeRepository->findOneBy(['code' => TypeActe::CODE_AUTORISATION_ABSENCE])
                ?? $typeActeRepository->findOneBy(['code' => TypeActe::CODE_DECISION])
                ?? $typeActeRepository->findOneBy([]);

            if (!$typeActe) {
                return null;
            }

            $employe = $absence->getEmploye();
            $nomAgent = $employe ? $employe->getNomComplet() : 'Agent';
            $numero = $numeroActe ?? sprintf('AA-%s-%04d', $absence->getDateDebut()->format('Y'), $absence->getId() ?? rand(1000, 9999));

            return $this->acteService->creerActe(
                institution: $institution,
                typeActe: $typeActe,
                numero: $numero,
                dateSignature: new \DateTime(),
                dateEffet: $absence->getDateDebut(),
                objet: sprintf(
                    'Autorisation d\'absence accordée à %s (%s du %s au %s)',
                    $nomAgent,
                    $absence->getTypeAbsence()->getLibelle(),
                    $absence->getDateDebut()->format('d/m/Y'),
                    $absence->getDateFin()->format('d/m/Y')
                ),
                signataireQualite: 'L\'Autorité Compétente',
                signataireNom: $approbateur->getNomComplet(),
                statut: Acte::STATUT_SIGNE,
                datePublication: new \DateTime(),
                contenuResume: sprintf(
                    'Autorisation d\'absence accordée pour une durée de %s %s. Motif : %s',
                    $absence->getDuree(),
                    strtolower($absence->getUnite()),
                    $absence->getMotif()
                ),
                employe: $employe,
                document: $absence->getDocument(),
                currentUser: $approbateur
            );
        } catch (\Throwable) {
            // Ne bloque pas l'approbation si la génération d'acte échoue
            return null;
        }
    }

    private function creerLog(
        Absence $absence,
        string $action,
        string $actionLabel,
        ?string $description,
        ?User $user
    ): AbsenceLog {
        if ($user === null) {
            $user = $absence->getEmploye()?->getUser();
            if ($user === null) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy([]) ?? new User();
            }
        }

        $log = new AbsenceLog();
        $log->setAction($action)
            ->setActionLabel($actionLabel)
            ->setDescription($description)
            ->setUser($user)
            ->setCreatedAt(new \DateTime());

        $absence->addAbsenceLog($log);
        $this->entityManager->persist($log);

        return $log;
    }
}
