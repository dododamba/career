<?php

namespace App\Service;

use App\Entity\Acte;
use App\Entity\Conges;
use App\Entity\CongesLog;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\SoldeConge;
use App\Entity\TypeActe;
use App\Entity\TypeConges;
use App\Entity\User;
use App\Repository\AbsenceRepository;
use App\Repository\ActeRepository;
use App\Repository\CongesRepository;
use App\Repository\SoldeCongeRepository;
use App\Repository\TypeCongesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CongeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CongesRepository $congesRepository,
        private readonly SoldeCongeRepository $soldeCongeRepository,
        private readonly TypeCongesRepository $typeCongesRepository,
        private readonly ?ActeService $acteService = null,
        private readonly ?SecurityAuditService $auditService = null,
        private readonly ?AbsenceRepository $absenceRepository = null
    ) {
    }

    /**
     * Crée et soumet une nouvelle demande de congé avec contrôle anti-chevauchement et vérification de solde.
     */
    public function demanderConge(
        Employe $employe,
        TypeConges $typeConge,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?string $motif = null,
        ?Document $document = null,
        ?User $demandeur = null
    ): Conges {
        if ($dateFin < $dateDebut) {
            throw new \InvalidArgumentException('La date de fin ne peut pas être antérieure à la date de début.');
        }

        $institution = $employe->getInstitution();

        // 1. Contrôle multi-tenant anti-IDOR
        if ($document !== null && $institution !== null) {
            if ($document->getInstitution() !== null && $document->getInstitution()->getId() !== $institution->getId()) {
                throw new AccessDeniedException('Le document justificatif spécifié n\'appartient pas à la même institution.');
            }
        }

        if ($typeConge->getInstitution() !== null && $institution !== null) {
            if ($typeConge->getInstitution()->getId() !== $institution->getId()) {
                throw new AccessDeniedException('Ce type de congé n\'est pas accessible pour cette institution.');
            }
        }

        // 2. Anti-chevauchement : pas de congé actif ou en attente sur la même période
        if ($this->verifierChevauchement($employe, $dateDebut, $dateFin)) {
            throw new \LogicException('L\'employé a déjà une demande de congé ou absence en cours ou validée sur cette période.');
        }

        // 3. Justificatif obligatoire si requis par le type de congé
        if ($typeConge->isJustificatifRequis() && $document === null) {
            throw new \LogicException(sprintf('Un document justificatif est obligatoire pour le type de congé "%s".', $typeConge->getLibelle()));
        }

        // 4. Calcul de la durée (jours calendaires par défaut)
        $jours = $this->calculerDureeCalendaire($dateDebut, $dateFin);
        $annee = (int) $dateDebut->format('Y');

        // 5. Vérification du solde si déductible
        if ($typeConge->isDeductible()) {
            $solde = $this->getSolde($employe, $typeConge, $annee);
            if ($solde === null) {
                // Initialiser le solde si non existant
                $solde = $this->initialiserSoldePourType($employe, $typeConge, $annee);
            }

            if ((float) $solde->getSolde() < (float) $jours) {
                throw new \LogicException(sprintf(
                    'Solde de congés insuffisant pour "%s" : %s jour(s) disponible(s), %d jour(s) demandé(s).',
                    $typeConge->getLibelle(),
                    $solde->getSolde(),
                    $jours
                ));
            }
        }

        // 6. Création de l'entité Conges
        $conge = new Conges();
        $conge->setEmploye($employe)
            ->setTypeConge($typeConge)
            ->setInstitution($institution)
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setDateDiff($jours)
            ->setMotif($motif)
            ->setDocument($document)
            ->setStatut(Conges::STATUT_EN_ATTENTE)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($conge);

        // 7. Création de l'historique CongesLog
        $this->creerLog(
            $conge,
            'create',
            'Demande soumise',
            sprintf('Demande de %d jour(s) du %s au %s', $jours, $dateDebut->format('d/m/Y'), $dateFin->format('d/m/Y')),
            $demandeur
        );

        // 8. Audit log
        $this->auditService?->log(
            'CONGE_REQUEST',
            'Demande de congé',
            $demandeur?->getUserIdentifier(),
            $demandeur,
            sprintf('Nouvelle demande pour l\'agent %s (#%d)', $employe->getNomComplet(), $employe->getId())
        );

        $this->entityManager->flush();

        return $conge;
    }

    /**
     * Approuve une demande de congé, décrémente le solde et optionnellement génère l'acte administratif.
     */
    public function approuverConge(
        Conges $conge,
        User $approbateur,
        bool $genererActe = true,
        ?string $numeroActe = null
    ): Conges {
        if ($conge->getStatut() !== Conges::STATUT_EN_ATTENTE) {
            throw new \LogicException('Seule une demande de congé en attente peut être approuvée.');
        }

        // Anti-self-approval
        $employe = $conge->getEmploye();
        if ($employe) {
            if ($employe->getUser() && $employe->getUser()->getId() === $approbateur->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas approuver votre propre demande de congé.');
            }
            if ($approbateur->getEmploye() && $approbateur->getEmploye()->getId() === $employe->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas approuver votre propre demande de congé.');
            }
        }

        // Mise à jour du statut
        $conge->setStatut(Conges::STATUT_APPROUVE);
        $conge->setApprouvePar($approbateur);
        $conge->setDateApprobation(new \DateTime());
        $conge->setUpdatedAt(new \DateTime());

        // Décrémentation du solde si déductible
        if ($conge->getTypeConge()->isDeductible() && $employe) {
            $annee = (int) $conge->getDateDebut()->format('Y');
            $this->debiterSolde($employe, $conge->getTypeConge(), (float) $conge->getDateDiff(), $annee);
        }

        // Génération optionnelle de l'acte administratif
        if ($genererActe && $conge->getInstitution() !== null && $this->acteService !== null && $employe !== null) {
            $acte = $this->genererActePourConge($conge, $approbateur, $numeroActe);
            if ($acte !== null) {
                $conge->setActe($acte);
            }
        }

        // Log d'approbation
        $this->creerLog(
            $conge,
            'approve',
            'Demande approuvée',
            sprintf('Approuvé par %s le %s', $approbateur->getNomComplet() ?? $approbateur->getUserIdentifier(), (new \DateTime())->format('d/m/Y H:i')),
            $approbateur
        );

        // Audit log
        $this->auditService?->log(
            'CONGE_APPROVE',
            'Approbation de congé',
            $approbateur->getUserIdentifier(),
            $approbateur,
            sprintf('Congé #%d approuvé pour %s', $conge->getId(), $employe?->getNomComplet() ?? 'Agent')
        );

        $this->entityManager->flush();

        return $conge;
    }

    /**
     * Rejette une demande de congé avec motif obligatoire.
     */
    public function rejeterConge(Conges $conge, User $refuseur, string $motifRefus): Conges
    {
        if ($conge->getStatut() !== Conges::STATUT_EN_ATTENTE) {
            throw new \LogicException('Seule une demande de congé en attente peut être rejetée.');
        }

        $employe = $conge->getEmploye();
        if ($employe) {
            if ($employe->getUser() && $employe->getUser()->getId() === $refuseur->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas rejeter votre propre demande de congé.');
            }
        }

        $conge->setStatut(Conges::STATUT_REFUSE);
        $conge->setCommentaireRefus($motifRefus);
        $conge->setApprouvePar($refuseur);
        $conge->setDateApprobation(new \DateTime());
        $conge->setUpdatedAt(new \DateTime());

        $this->creerLog(
            $conge,
            'reject',
            'Demande rejetée',
            sprintf('Motif : %s', $motifRefus),
            $refuseur
        );

        $this->auditService?->log(
            'CONGE_REJECT',
            'Rejet de congé',
            $refuseur->getUserIdentifier(),
            $refuseur,
            sprintf('Congé #%d rejeté. Motif: %s', $conge->getId(), $motifRefus)
        );

        $this->entityManager->flush();

        return $conge;
    }

    /**
     * Annule une demande de congé (en attente ou déjà approuvée avec récrédit du solde).
     */
    public function annulerConge(Conges $conge, User $annuleur, ?string $motifAnnulation = null): Conges
    {
        if ($conge->getStatut() !== Conges::STATUT_EN_ATTENTE && $conge->getStatut() !== Conges::STATUT_APPROUVE) {
            throw new \LogicException('Seul un congé en attente ou approuvé peut être annulé.');
        }

        $ancienStatut = $conge->getStatut();
        $employe = $conge->getEmploye();

        // Si le congé avait été approuvé et déduit, rembourser le solde
        if ($ancienStatut === Conges::STATUT_APPROUVE && $conge->getTypeConge()->isDeductible() && $employe !== null) {
            $annee = (int) $conge->getDateDebut()->format('Y');
            $this->crediterSolde($employe, $conge->getTypeConge(), (float) $conge->getDateDiff(), $annee);
        }

        // Si un acte était lié, marquer son statut en ABROGE
        if ($conge->getActe() !== null) {
            $conge->getActe()->setStatut(Acte::STATUT_ABROGE);
        }

        $conge->setStatut(Conges::STATUT_ANNULE);
        $conge->setUpdatedAt(new \DateTime());

        $this->creerLog(
            $conge,
            'cancel',
            'Demande annulée',
            $motifAnnulation ?? 'Congé annulé',
            $annuleur
        );

        $this->auditService?->log(
            'CONGE_CANCEL',
            'Annulation de congé',
            $annuleur->getUserIdentifier(),
            $annuleur,
            sprintf('Congé #%d annulé (ancien statut : %d)', $conge->getId(), $ancienStatut)
        );

        $this->entityManager->flush();

        return $conge;
    }

    /**
     * Vérifie s'il existe un chevauchement avec un autre congé actif ou en attente pour cet employé.
     */
    public function verifierChevauchement(
        Employe $employe,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeCongeId = null
    ): bool {
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

        if ($excludeCongeId !== null) {
            $qb->andWhere('c.id != :excludeId')
                ->setParameter('excludeId', $excludeCongeId);
        }

        if ((int) $qb->getQuery()->getSingleScalarResult() > 0) {
            return true;
        }

        if ($this->absenceRepository !== null) {
            $overlappingAbsences = $this->absenceRepository->findOverlapping($employe, $dateDebut, $dateFin);
            if (!empty($overlappingAbsences)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Récupère le solde de congé d'un employé pour un type et une année donnés.
     */
    public function getSolde(Employe $employe, TypeConges $typeConge, ?int $annee = null): ?SoldeConge
    {
        $annee = $annee ?? (int) date('Y');

        return $this->soldeCongeRepository->findOneByEmployeAndTypeAndAnnee($employe, $typeConge, $annee);
    }

    /**
     * Débite le solde de congé d'un agent.
     */
    public function debiterSolde(Employe $employe, TypeConges $typeConge, float $jours, ?int $annee = null): SoldeConge
    {
        $annee = $annee ?? (int) date('Y');
        $solde = $this->getSolde($employe, $typeConge, $annee);

        if ($solde === null) {
            $solde = $this->initialiserSoldePourType($employe, $typeConge, $annee);
        }

        $solde->debiter($jours);
        $this->entityManager->persist($solde);

        return $solde;
    }

    /**
     * Recrédite le solde de congé d'un agent (après annulation).
     */
    public function crediterSolde(Employe $employe, TypeConges $typeConge, float $jours, ?int $annee = null): SoldeConge
    {
        $annee = $annee ?? (int) date('Y');
        $solde = $this->getSolde($employe, $typeConge, $annee);

        if ($solde === null) {
            $solde = $this->initialiserSoldePourType($employe, $typeConge, $annee);
        }

        $solde->crediter($jours);
        $this->entityManager->persist($solde);

        return $solde;
    }

    /**
     * Initialise les soldes annuels d'un agent pour tous les types de congés actifs.
     *
     * @return SoldeConge[]
     */
    public function initialiserSoldesAgent(Employe $employe, ?int $annee = null): array
    {
        $annee = $annee ?? (int) date('Y');
        $types = $this->typeCongesRepository->findAllActive();
        $soldes = [];

        foreach ($types as $type) {
            $solde = $this->getSolde($employe, $type, $annee);
            if ($solde === null) {
                $solde = $this->initialiserSoldePourType($employe, $type, $annee);
            }
            $soldes[] = $solde;
        }

        $this->entityManager->flush();

        return $soldes;
    }

    /**
     * Récupère tous les soldes disponibles pour un agent pour l'année courante.
     *
     * @return SoldeConge[]
     */
    public function getSoldesAgent(Employe $employe, ?int $annee = null): array
    {
        $annee = $annee ?? (int) date('Y');
        $soldes = $this->soldeCongeRepository->findByEmployeAndAnnee($employe, $annee);

        if (empty($soldes)) {
            $soldes = $this->initialiserSoldesAgent($employe, $annee);
        }

        return $soldes;
    }

    /**
     * Calcule la durée calendaire en jours entre deux dates (incluses).
     */
    public function calculerDureeCalendaire(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin): int
    {
        $diff = $dateDebut->diff($dateFin);

        return $diff->days + 1;
    }

    /**
     * Calcule la durée en jours ouvrés (du lundi au vendredi inclus).
     */
    public function calculerDureeJoursOuvres(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin): int
    {
        $current = clone $dateDebut;
        $end = clone $dateFin;
        $workingDays = 0;

        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('N'); // 1 (Mon) to 7 (Sun)
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $workingDays++;
            }
            $current->modify('+1 day');
        }

        return $workingDays;
    }

    /**
     * Récupère les métriques clés de congés pour une institution.
     */
    public function getStatistiquesInstitution(Institution $institution, ?int $annee = null): array
    {
        $annee = $annee ?? (int) date('Y');

        $qb = $this->congesRepository->createQueryBuilder('c')
            ->leftJoin('c.employe', 'e')
            ->where('c.institution = :institution OR (c.institution IS NULL AND e.institution = :institution)')
            ->setParameter('institution', $institution);

        $allConges = $qb->getQuery()->getResult();

        $enAttente = 0;
        $approuves = 0;
        $refuses = 0;
        $annules = 0;
        $totalJoursApprouves = 0;

        foreach ($allConges as $conge) {
            match ($conge->getStatut()) {
                Conges::STATUT_EN_ATTENTE => $enAttente++,
                Conges::STATUT_APPROUVE => [
                    $approuves++,
                    $totalJoursApprouves += (int) $conge->getDateDiff(),
                ],
                Conges::STATUT_REFUSE => $refuses++,
                Conges::STATUT_ANNULE => $annules++,
                default => null,
            };
        }

        return [
            'total' => count($allConges),
            'en_attente' => $enAttente,
            'approuves' => $approuves,
            'refuses' => $refuses,
            'annules' => $annules,
            'total_jours_pris' => $totalJoursApprouves,
        ];
    }

    private function initialiserSoldePourType(Employe $employe, TypeConges $typeConge, int $annee): SoldeConge
    {
        $solde = new SoldeConge();
        $solde->setEmploye($employe)
            ->setTypeConge($typeConge)
            ->setInstitution($employe->getInstitution())
            ->setAnnee($annee)
            ->setSoldeAcquis((string) $typeConge->getJoursParAn())
            ->setSoldePris('0.00')
            ->setSolde((string) $typeConge->getJoursParAn());

        $this->entityManager->persist($solde);

        return $solde;
    }

    private function genererActePourConge(Conges $conge, User $approbateur, ?string $numeroActe = null): ?Acte
    {
        try {
            $institution = $conge->getInstitution();
            if (!$institution) {
                return null;
            }

            // Chercher le TypeActe TITRE_CONGE ou DECISION
            $typeActeRepository = $this->entityManager->getRepository(TypeActe::class);
            $typeActe = $typeActeRepository->findOneBy(['code' => 'TITRE_CONGE'])
                ?? $typeActeRepository->findOneBy(['code' => 'DECISION'])
                ?? $typeActeRepository->findOneBy([]);

            if (!$typeActe) {
                return null;
            }

            $employe = $conge->getEmploye();
            $nomAgent = $employe ? $employe->getNomComplet() : 'Agent';
            $numero = $numeroActe ?? sprintf('TC-%s-%04d', $conge->getDateDebut()->format('Y'), $conge->getId() ?? rand(1000, 9999));

            return $this->acteService->creerActe(
                institution: $institution,
                typeActe: $typeActe,
                numero: $numero,
                dateSignature: new \DateTime(),
                dateEffet: $conge->getDateDebut(),
                objet: sprintf('Titre de congé pour %s (%s du %s au %s)', $nomAgent, $conge->getTypeConge()->getLibelle(), $conge->getDateDebut()->format('d/m/Y'), $conge->getDateFin()->format('d/m/Y')),
                signataireQualite: 'L\'Autorité Compétente',
                signataireNom: $approbateur->getNomComplet(),
                statut: Acte::STATUT_SIGNE,
                datePublication: new \DateTime(),
                contenuResume: sprintf('Congé accordé de %d jour(s) pour motif : %s', $conge->getDateDiff(), $conge->getMotif() ?? 'Régulier'),
                employe: $employe,
                currentUser: $approbateur
            );
        } catch (\Throwable $e) {
            // Ne bloque pas l'approbation du congé si la génération de l'acte échoue
            return null;
        }
    }

    private function creerLog(
        Conges $conge,
        string $action,
        string $actionLabel,
        ?string $description,
        ?User $user
    ): CongesLog {
        // Fallback user if not specified
        if ($user === null) {
            $user = $conge->getEmploye()?->getUser();
            if ($user === null) {
                // Find any admin or system user
                $user = $this->entityManager->getRepository(User::class)->findOneBy([]) ?? new User();
            }
        }

        $log = new CongesLog();
        $log->setAction($action)
            ->setActionLabel($actionLabel)
            ->setDescription($description)
            ->setUser($user)
            ->setCreatedAt(new \DateTime());

        $conge->addCongesLog($log);
        $this->entityManager->persist($log);

        return $log;
    }
}
