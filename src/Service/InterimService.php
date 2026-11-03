<?php

namespace App\Service;

use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Interim;
use App\Entity\InterimLog;
use App\Entity\Poste;
use App\Entity\Structure;
use App\Entity\TypeActe;
use App\Entity\User;
use App\MultiTenancy\TenantContext;
use App\Repository\AbsenceRepository;
use App\Repository\CongesRepository;
use App\Repository\InterimRepository;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class InterimService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InterimRepository $interimRepository,
        private CongesRepository $congesRepository,
        private AbsenceRepository $absenceRepository,
        private MissionRepository $missionRepository,
        private TenantContext $tenantContext,
        private SecurityAuditService $auditService,
        private ?ActeService $acteService = null
    ) {
    }

    /**
     * Crée une désignation d'intérim ou de délégation administrative.
     */
    public function creerInterim(
        Institution $institution,
        Employe $titulaire,
        Employe $interimaire,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        string $motif,
        string $typeDelegation = Interim::TYPE_INTERIM_TOTAL,
        array $perimetre = [Interim::POUVOIR_TOUS],
        ?Poste $poste = null,
        ?Structure $structure = null,
        ?string $reference = null,
        bool $soumettreDirectement = false,
        ?User $demandeur = null
    ): Interim {
        // 1. Validation de cohérence des dates
        if ($dateDebut > $dateFin) {
            throw new \LogicException('La date de début ne peut pas être postérieure à la date de fin.');
        }

        // 2. Incompatibilité : titulaire et intérimaire doivent être distincts
        if ($titulaire->getId() === $interimaire->getId()) {
            throw new \LogicException('L\'intérimaire désigné doit être une personne distincte du titulaire remplacé.');
        }

        // 3. Statut actif de l'intérimaire
        if (!$interimaire->isActif()) {
            throw new \LogicException(sprintf('L\'agent %s est inactif et ne peut pas être désigné intérimaire.', $interimaire->getNomComplet()));
        }

        // 4. Contrôles anti-IDOR : tous les acteurs doivent appartenir à l'institution
        if ($titulaire->getInstitution() && $titulaire->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Le titulaire n\'appartient pas à cette institution.');
        }

        if ($interimaire->getInstitution() && $interimaire->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('L\'intérimaire n\'appartient pas à cette institution.');
        }

        if ($poste !== null && $poste->getInstitution() && $poste->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Le poste spécifié n\'appartient pas à cette institution.');
        }

        if ($structure !== null && $structure->getInstitution() && $structure->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('La structure spécifiée n\'appartient pas à cette institution.');
        }

        // 5. Vérification de disponibilité de l'intérimaire
        $this->verifierDisponibiliteInterimaire($interimaire, $dateDebut, $dateFin);

        // 6. Vérification chevauchement d'intérim pour le titulaire
        $overlappingTitulaire = $this->interimRepository->findOverlappingForTitulaire($titulaire, $dateDebut, $dateFin);
        if (!empty($overlappingTitulaire)) {
            throw new \LogicException(sprintf(
                'Un intérim est déjà programmé pour le titulaire %s sur cette période (Réf: %s).',
                $titulaire->getNomComplet(),
                $overlappingTitulaire[0]->getReference()
            ));
        }

        // 7. Génération de référence
        $reference = $reference ?? $this->genererReference($institution, $dateDebut);

        // 8. Déduction du poste/structure si non renseignés explicitement
        $poste = $poste ?? $titulaire->getPoste();
        $structure = $structure ?? $titulaire->getStructure() ?? $poste?->getStructure();

        // 9. Création de l'entité Interim
        $interim = new Interim();
        $interim->setInstitution($institution)
            ->setReference($reference)
            ->setTitulaire($titulaire)
            ->setInterimaire($interimaire)
            ->setPoste($poste)
            ->setStructure($structure)
            ->setTypeDelegation($typeDelegation)
            ->setPerimetre(empty($perimetre) ? [Interim::POUVOIR_TOUS] : array_values(array_unique($perimetre)))
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setMotif(trim($motif))
            ->setStatut($soumettreDirectement ? Interim::STATUT_SOUMIS : Interim::STATUT_BROUILLON)
            ->setCreePar($demandeur)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($interim);

        // 10. Audit log
        $action = $soumettreDirectement ? 'submit' : 'create';
        $actionLabel = $soumettreDirectement ? 'Intérim créé et soumis pour validation' : 'Intérim créé (Brouillon)';
        $this->creerLog(
            $interim,
            $action,
            $actionLabel,
            sprintf('Désignation de %s en remplacement de %s du %s au %s', $interimaire->getNomComplet(), $titulaire->getNomComplet(), $dateDebut->format('d/m/Y'), $dateFin->format('d/m/Y')),
            $demandeur
        );

        $this->auditService->log(
            'INTERIM_CREATE',
            'Création d\'intérim',
            $demandeur?->getUserIdentifier() ?? 'system',
            $demandeur,
            sprintf('Intérim %s créé : %s remplace %s', $reference, $interimaire->getNomComplet(), $titulaire->getNomComplet())
        );

        $this->entityManager->flush();

        return $interim;
    }

    /**
     * Modifie un intérim (autorisé uniquement en statut brouillon ou soumis).
     */
    public function modifierInterim(
        Interim $interim,
        Employe $titulaire,
        Employe $interimaire,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        string $motif,
        string $typeDelegation,
        array $perimetre,
        ?Poste $poste = null,
        ?Structure $structure = null,
        ?User $modificateur = null
    ): Interim {
        if (!in_array($interim->getStatut(), [Interim::STATUT_BROUILLON, Interim::STATUT_SOUMIS], true)) {
            throw new \LogicException('Seul un intérim en brouillon ou en attente peut être modifié.');
        }

        if ($dateDebut > $dateFin) {
            throw new \LogicException('La date de début ne peut pas être postérieure à la date de fin.');
        }

        if ($titulaire->getId() === $interimaire->getId()) {
            throw new \LogicException('L\'intérimaire désigné doit être une personne distincte du titulaire remplacé.');
        }

        // Vérification disponibilité si intérimaire ou dates modifiés
        if ($interim->getInterimaire()->getId() !== $interimaire->getId() || $interim->getDateDebut() != $dateDebut || $interim->getDateFin() != $dateFin) {
            $this->verifierDisponibiliteInterimaire($interimaire, $dateDebut, $dateFin, $interim->getId());
        }

        $interim->setTitulaire($titulaire)
            ->setInterimaire($interimaire)
            ->setPoste($poste)
            ->setStructure($structure)
            ->setTypeDelegation($typeDelegation)
            ->setPerimetre(empty($perimetre) ? [Interim::POUVOIR_TOUS] : array_values(array_unique($perimetre)))
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setMotif(trim($motif))
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $interim,
            'update',
            'Intérim modifié',
            sprintf('Paramètres mis à jour par %s', $modificateur ? ($modificateur->getNomComplet() ?? $modificateur->getUserIdentifier()) : 'l\'utilisateur'),
            $modificateur
        );

        $this->entityManager->flush();

        return $interim;
    }

    /**
     * Soumet un intérim brouillon pour validation hiérarchique.
     */
    public function soumettreInterim(Interim $interim, ?User $utilisateur = null): Interim
    {
        if ($interim->getStatut() !== Interim::STATUT_BROUILLON) {
            throw new \LogicException('Seul un intérim en brouillon peut être soumis pour validation.');
        }

        $interim->setStatut(Interim::STATUT_SOUMIS);
        $interim->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $interim,
            'submit',
            'Intérim soumis pour validation',
            sprintf('Soumission pour validation de la désignation de %s', $interim->getInterimaire()->getNomComplet()),
            $utilisateur
        );

        $this->entityManager->flush();

        return $interim;
    }

    /**
     * Valide l'intérim et l'active immédiatement ou à date.
     * Génère optionnellement l'Acte officiel de décision d'intérim.
     */
    public function validerInterim(
        Interim $interim,
        User $validateur,
        bool $genererDecision = true,
        ?string $numeroActe = null,
        ?string $commentaire = null
    ): Interim {
        if (!in_array($interim->getStatut(), [Interim::STATUT_SOUMIS, Interim::STATUT_BROUILLON], true)) {
            throw new \LogicException('Seul un intérim en attente de validation peut être validé.');
        }

        // Règle d'anti-auto-validation
        $this->verifierNonParticipationValidateur($interim, $validateur);

        $interim->setStatut(Interim::STATUT_ACTIF);
        $interim->setValidePar($validateur);
        $interim->setDateValidation(new \DateTime());
        $interim->setCommentaireValidation($commentaire ? trim($commentaire) : null);
        $interim->setUpdatedAt(new \DateTimeImmutable());

        // Génération automatique de l'Acte officiel de décision d'intérim
        if ($genererDecision && $this->acteService !== null) {
            $acte = $this->genererDecisionInterimOfficielle($interim, $validateur, $numeroActe);
            if ($acte !== null) {
                $interim->setActe($acte);
            }
        }

        $this->creerLog(
            $interim,
            'validate',
            'Intérim validé & Actif',
            sprintf('Validé par %s. %s', $validateur->getNomComplet() ?? $validateur->getUserIdentifier(), $commentaire ? ('Commentaire: ' . trim($commentaire)) : ''),
            $validateur
        );

        $this->auditService->log(
            'INTERIM_VALIDATE',
            'Validation d\'intérim',
            $validateur->getUserIdentifier(),
            $validateur,
            sprintf('Intérim %s validé et activé pour %s', $interim->getReference(), $interim->getInterimaire()->getNomComplet())
        );

        $this->entityManager->flush();

        return $interim;
    }

    /**
     * Refuse un intérim avec motif obligatoire.
     */
    public function refuserInterim(Interim $interim, User $refuseur, string $motifRefus): Interim
    {
        if ($interim->getStatut() !== Interim::STATUT_SOUMIS) {
            throw new \LogicException('Seul un intérim soumis pour validation peut être refusé.');
        }

        if (trim($motifRefus) === '') {
            throw new \LogicException('Le motif du refus est obligatoire.');
        }

        $this->verifierNonParticipationValidateur($interim, $refuseur);

        $interim->setStatut(Interim::STATUT_REFUSE);
        $interim->setCommentaireValidation(trim($motifRefus));
        $interim->setMotifFin(trim($motifRefus));
        $interim->setDateValidation(new \DateTime());
        $interim->setValidePar($refuseur);
        $interim->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $interim,
            'reject',
            'Intérim refusé',
            sprintf('Refusé par %s. Motif : %s', $refuseur->getNomComplet() ?? $refuseur->getUserIdentifier(), trim($motifRefus)),
            $refuseur
        );

        $this->entityManager->flush();

        return $interim;
    }

    /**
     * Révoque prématurément un intérim actif (avec motif obligatoire et abrogation de l'acte éventuel).
     */
    public function revoquerInterim(Interim $interim, User $utilisateur, string $motifRevoquation): Interim
    {
        if ($interim->getStatut() !== Interim::STATUT_ACTIF) {
            throw new \LogicException('Seul un intérim actif peut être révoqué.');
        }

        if (trim($motifRevoquation) === '') {
            throw new \LogicException('Le motif de la révocation est obligatoire.');
        }

        // Abrogation de l'acte administratif associé le cas échéant
        if ($interim->getActe() !== null) {
            $interim->getActe()->setStatut(Acte::STATUT_ABROGE);
        }

        $interim->setStatut(Interim::STATUT_REVOQUE);
        $interim->setDateFinEffective(new \DateTime());
        $interim->setMotifFin('Révocation : ' . trim($motifRevoquation));
        $interim->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $interim,
            'revoke',
            'Intérim révoqué',
            sprintf('Révoqué par %s. Motif : %s', $utilisateur->getNomComplet() ?? $utilisateur->getUserIdentifier(), trim($motifRevoquation)),
            $utilisateur
        );

        $this->auditService->log(
            'INTERIM_REVOKE',
            'Révocation d\'intérim',
            $utilisateur->getUserIdentifier(),
            $utilisateur,
            sprintf('Intérim %s révoqué pour %s', $interim->getReference(), $interim->getInterimaire()->getNomComplet())
        );

        $this->entityManager->flush();

        return $interim;
    }

    /**
     * Clôture un intérim arrivé à échéance ou achevé normalement.
     */
    public function cloturerInterim(Interim $interim, ?User $utilisateur = null, ?string $motif = null): Interim
    {
        if (!in_array($interim->getStatut(), [Interim::STATUT_ACTIF, Interim::STATUT_SOUMIS], true)) {
            throw new \LogicException('Cet intérim ne peut pas être clôturé dans son état actuel.');
        }

        $interim->setStatut(Interim::STATUT_TERMINE);
        if ($interim->getDateFinEffective() === null) {
            $interim->setDateFinEffective(new \DateTime());
        }
        if ($motif !== null) {
            $interim->setMotifFin(trim($motif));
        }
        $interim->setUpdatedAt(new \DateTimeImmutable());

        $this->creerLog(
            $interim,
            'close',
            'Intérim clôturé / échu',
            sprintf('Fin de la période d\'intérim%s', $motif ? (' : ' . trim($motif)) : ''),
            $utilisateur
        );

        $this->entityManager->flush();

        return $interim;
    }

    /**
     * Supprime un intérim en brouillon ou refusé.
     */
    public function supprimerInterim(Interim $interim, ?User $utilisateur = null): void
    {
        if (in_array($interim->getStatut(), [Interim::STATUT_ACTIF, Interim::STATUT_TERMINE], true)) {
            throw new \LogicException('Impossible de supprimer un intérim actif ou historisé. Veuillez le révoquer.');
        }

        $this->auditService->log(
            'INTERIM_DELETE',
            'Suppression d\'intérim',
            $utilisateur?->getUserIdentifier() ?? 'system',
            $utilisateur,
            sprintf('Intérim %s supprimé', $interim->getReference())
        );

        $this->entityManager->remove($interim);
        $this->entityManager->flush();
    }

    /**
     * Vérifie la disponibilité stricte de l'agent désigné pour assurer l'intérim.
     */
    public function verifierDisponibiliteInterimaire(
        Employe $interimaire,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeInterimId = null
    ): void {
        // 1. Vérification congés
        $qbConges = $this->congesRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.employe = :employe')
            ->andWhere('c.statut IN (:statuts)')
            ->andWhere('c.dateDebut <= :dateFin')
            ->andWhere('c.dateFin >= :dateDebut')
            ->setParameter('employe', $interimaire)
            ->setParameter('statuts', [Conges::STATUT_EN_ATTENTE, Conges::STATUT_APPROUVE])
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'));

        if ((int) $qbConges->getQuery()->getSingleScalarResult() > 0) {
            throw new \LogicException(sprintf(
                'L\'intérimaire désigné %s est indisponible : un congé est déjà programmé sur cette période.',
                $interimaire->getNomComplet()
            ));
        }

        // 2. Vérification autorisations d'absence
        $overlappingAbsences = $this->absenceRepository->findOverlapping($interimaire, $dateDebut, $dateFin);
        if (!empty($overlappingAbsences)) {
            throw new \LogicException(sprintf(
                'L\'intérimaire désigné %s est indisponible : une autorisation d\'absence est déjà enregistrée sur cette période.',
                $interimaire->getNomComplet()
            ));
        }

        // 3. Vérification missions
        $overlappingMissions = $this->missionRepository->findOverlappingForEmploye($interimaire, $dateDebut, $dateFin);
        if (!empty($overlappingMissions)) {
            throw new \LogicException(sprintf(
                'L\'intérimaire désigné %s est engagé sur une mission active (%s) sur cette période.',
                $interimaire->getNomComplet(),
                $overlappingMissions[0]->getReference()
            ));
        }

        // 4. Vérification cumul d'intérims totaux incompatibles
        $overlappingInterims = $this->interimRepository->findOverlappingForInterimaire($interimaire, $dateDebut, $dateFin, $excludeInterimId);
        if (!empty($overlappingInterims)) {
            throw new \LogicException(sprintf(
                'L\'intérimaire désigné %s assure déjà un intérim total actif ou programmé sur cette période (%s).',
                $interimaire->getNomComplet(),
                $overlappingInterims[0]->getReference()
            ));
        }
    }

    /**
     * Recherche si un agent bénéficie actuellement d'une délégation active (pour un pouvoir particulier).
     */
    public function getInterimActifPourAgent(
        Employe $interimaire,
        ?string $pouvoir = null,
        ?\DateTimeInterface $date = null
    ): ?Interim {
        $interims = $this->interimRepository->findActiveForInterimaire($interimaire, $date);

        if (empty($interims)) {
            return null;
        }

        if ($pouvoir === null) {
            return $interims[0];
        }

        foreach ($interims as $int) {
            if ($int->hasPouvoir($pouvoir)) {
                return $int;
            }
        }

        return null;
    }

    /**
     * Routine d'expiration automatique des intérims dont la date de fin est révolue.
     *
     * @return int Nombre d'intérims clôturés automatiquement
     */
    public function verifierEtMettreAJourExpirations(?\DateTimeInterface $dateReference = null): int
    {
        $date = $dateReference ?? new \DateTime();
        $expiredInterims = $this->interimRepository->findExpiredActiveInterims($date);
        $count = 0;

        foreach ($expiredInterims as $interim) {
            $interim->setStatut(Interim::STATUT_TERMINE);
            $interim->setDateFinEffective($interim->getDateFin());
            $interim->setMotifFin('Expiration automatique de la période d\'intérim');
            $interim->setUpdatedAt(new \DateTimeImmutable());

            $this->creerLog(
                $interim,
                'expire',
                'Expiration automatique',
                sprintf('Période d\'intérim échue au %s', $interim->getDateFin()->format('d/m/Y')),
                null
            );

            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Règle de séparation des fonctions : le validateur ne doit être ni le titulaire ni l'intérimaire.
     */
    private function verifierNonParticipationValidateur(Interim $interim, User $validateur): void
    {
        $employeValidateur = $validateur->getEmploye();

        if ($employeValidateur !== null) {
            if ($interim->getTitulaire() && $interim->getTitulaire()->getId() === $employeValidateur->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas valider ou refuser un intérim vous concernant en tant que titulaire.');
            }
            if ($interim->getInterimaire() && $interim->getInterimaire()->getId() === $employeValidateur->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas valider ou refuser votre propre désignation comme intérimaire.');
            }
        }

        if ($interim->getTitulaire() && $interim->getTitulaire()->getUser() && $interim->getTitulaire()->getUser()->getId() === $validateur->getId()) {
            throw new AccessDeniedException('Vous ne pouvez pas valider ou refuser un intérim vous concernant.');
        }

        if ($interim->getInterimaire() && $interim->getInterimaire()->getUser() && $interim->getInterimaire()->getUser()->getId() === $validateur->getId()) {
            throw new AccessDeniedException('Vous ne pouvez pas valider ou refuser votre propre désignation comme intérimaire.');
        }
    }

    /**
     * Génère l'acte administratif officiel (Décision d'intérim).
     */
    private function genererDecisionInterimOfficielle(Interim $interim, User $validateur, ?string $numeroActe = null): ?Acte
    {
        try {
            $institution = $interim->getInstitution();
            $typeActeRepository = $this->entityManager->getRepository(TypeActe::class);
            $typeActe = $typeActeRepository->findOneBy(['code' => TypeActe::CODE_DECISION_INTERIM])
                ?? $typeActeRepository->findOneBy(['code' => TypeActe::CODE_DECISION])
                ?? $typeActeRepository->findOneBy(['code' => TypeActe::CODE_NOTE_SERVICE])
                ?? $typeActeRepository->findOneBy([]);

            if (!$typeActe) {
                return null;
            }

            $annee = $interim->getDateDebut()->format('Y');
            $numero = $numeroActe ?? sprintf('DEC-INT-%s-%04d', $annee, $interim->getId() ?? rand(1000, 9999));

            $posteLabel = $interim->getPoste() ? $interim->getPoste()->getTitre() : ($interim->getTitulaire()->getFonction()?->getLibelle() ?? 'Fonction de direction');

            return $this->acteService->creerActe(
                institution: $institution,
                typeActe: $typeActe,
                numero: $numero,
                dateSignature: new \DateTime(),
                dateEffet: $interim->getDateDebut(),
                objet: sprintf(
                    'Désignation d\'intérim : %s remplace %s (%s)',
                    $interim->getInterimaire()->getNomComplet(),
                    $interim->getTitulaire()->getNomComplet(),
                    $posteLabel
                ),
                signataireQualite: 'L\'Autorité Compétente',
                signataireNom: $validateur->getNomComplet(),
                statut: Acte::STATUT_SIGNE,
                datePublication: new \DateTime(),
                contenuResume: sprintf(
                    'Intérim officiel confié à %s pour assurer le remplacement de %s sur le poste de %s du %s au %s. Type : %s. Motif : %s.',
                    $interim->getInterimaire()->getNomComplet(),
                    $interim->getTitulaire()->getNomComplet(),
                    $posteLabel,
                    $interim->getDateDebut()->format('d/m/Y'),
                    $interim->getDateFin()->format('d/m/Y'),
                    $interim->getTypeDelegationLabel(),
                    $interim->getMotif()
                ),
                employe: $interim->getInterimaire(),
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

        return sprintf('INT-%s-%04d', $annee, $random);
    }

    private function creerLog(
        Interim $interim,
        string $action,
        string $actionLabel,
        ?string $description,
        ?User $user
    ): InterimLog {
        if ($user === null) {
            $user = $interim->getCreePar() ?? $this->entityManager->getRepository(User::class)->findOneBy([]) ?? new User();
        }

        $log = new InterimLog();
        $log->setAction($action)
            ->setActionLabel($actionLabel)
            ->setDescription($description)
            ->setUser($user)
            ->setCreatedAt(new \DateTime());

        $interim->addInterimLog($log);
        $this->entityManager->persist($log);

        return $log;
    }
}
