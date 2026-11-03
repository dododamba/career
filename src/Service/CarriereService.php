<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Employe;
use App\Entity\Fonction;
use App\Entity\Institution;
use App\Entity\OccupationPoste;
use App\Entity\Poste;
use App\Entity\Service;
use App\Entity\Structure;
use App\Repository\AffectationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CarriereService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AffectationRepository $affectationRepository,
        private PosteService $posteService
    ) {
    }

    /**
     * Crée une proposition ou une affectation pour un agent avec traçabilité et intégrité multi-tenant.
     */
    public function creerMouvement(
        Employe $employe,
        string $typeMouvement,
        \DateTimeInterface $dateEffet,
        ?Structure $structure = null,
        ?Service $service = null,
        ?Poste $poste = null,
        ?Fonction $fonction = null,
        ?\DateTimeInterface $dateDecision = null,
        ?\DateTimeInterface $datePriseService = null,
        ?string $referenceActe = null,
        ?string $motif = null,
        ?string $commentaire = null,
        bool $autoValiderEtActiver = false
    ): Affectation {
        $institution = $employe->getInstitution();
        if (!$institution) {
            throw new AccessDeniedException('L\'employé n\'a pas d\'institution rattachée.');
        }

        // Contrôle d'isolation multi-tenant pour toutes les entités liées
        if ($structure && $structure->getInstitution() && $structure->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('La structure cible appartient à une autre institution.');
        }
        if ($service && $service->getInstitution() && $service->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Le service cible appartient à une autre institution.');
        }
        if ($poste && $poste->getInstitution() && $poste->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Le poste cible appartient à une autre institution.');
        }

        // Si le poste spécifie une structure/service/fonction et qu'ils ne sont pas passés, les déduire
        if ($poste) {
            $structure = $structure ?? $poste->getStructure();
            $service = $service ?? $poste->getService();
            $fonction = $fonction ?? $poste->getFonction();
        }

        $affectation = new Affectation();
        $affectation->setInstitution($institution);
        $affectation->setEmploye($employe);
        $affectation->setTypeMouvement($typeMouvement);
        $affectation->setDateDecision($dateDecision ?? new \DateTime());
        $affectation->setDateEffet($dateEffet);
        $affectation->setReferenceActe($referenceActe);
        $affectation->setMotif($motif);
        $affectation->setCommentaire($commentaire);

        // Référence automatique
        $prefix = match ($typeMouvement) {
            Affectation::TYPE_MUTATION => 'MUT',
            Affectation::TYPE_PROMOTION => 'PRM',
            Affectation::TYPE_DETACHEMENT => 'DET',
            Affectation::TYPE_CESSATION => 'CES',
            Affectation::TYPE_REINTEGRATION => 'REI',
            default => 'AFF',
        };
        $affectation->setReference($prefix . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)));

        // Capture de la situation précédente
        $affectation->setAncienneStructure($employe->getStructure());
        $affectation->setAncienService($employe->getService());
        $affectation->setAncienPoste($employe->getPoste());
        $affectation->setAncienneFonction($employe->getFonction());

        // Nouvelle affectation
        $affectation->setStructure($structure);
        $affectation->setService($service);
        $affectation->setPoste($poste);
        $affectation->setFonction($fonction);

        if ($autoValiderEtActiver) {
            $priseServiceDate = $datePriseService ?? $dateEffet;
            $this->activerAffectation($affectation, $priseServiceDate);
        } else {
            $affectation->setStatut(Affectation::STATUT_PROPOSEE);
            $this->entityManager->persist($affectation);
            $this->entityManager->flush();
        }

        return $affectation;
    }

    /**
     * Valide une proposition de mouvement (transition PROPOSEE -> VALIDEE)
     */
    public function validerMouvement(Affectation $affectation): Affectation
    {
        if ($affectation->getStatut() !== Affectation::STATUT_PROPOSEE) {
            throw new \LogicException('Seul un mouvement au statut PROPOSEE peut être validé.');
        }

        $affectation->setStatut(Affectation::STATUT_VALIDEE);
        $affectation->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return $affectation;
    }

    /**
     * Confirme la prise de service effective du mouvement (transition -> ACTIVE)
     * Clôture l'ancienne affectation et applique les changements au poste et à l'employé.
     */
    public function confirmerPriseDeService(Affectation $affectation, \DateTimeInterface $datePriseService): Affectation
    {
        if (!in_array($affectation->getStatut(), [Affectation::STATUT_PROPOSEE, Affectation::STATUT_VALIDEE], true)) {
            throw new \LogicException('Ce mouvement ne peut pas être activé dans son état actuel.');
        }

        $this->activerAffectation($affectation, $datePriseService);

        return $affectation;
    }

    /**
     * Annule un mouvement en attente
     */
    public function annulerMouvement(Affectation $affectation, ?string $motifAnnulation = null): Affectation
    {
        if (in_array($affectation->getStatut(), [Affectation::STATUT_ACTIVE, Affectation::STATUT_CLOTUREE], true)) {
            throw new \LogicException('Un mouvement déjà actif ou clôturé ne peut pas être simplement annulé. Créez un mouvement correctif.');
        }

        $affectation->setStatut(Affectation::STATUT_ANNULEE);
        $affectation->setUpdatedAt(new \DateTime());
        if ($motifAnnulation) {
            $comm = $affectation->getCommentaire() ? $affectation->getCommentaire() . "\n" : '';
            $affectation->setCommentaire($comm . '[ANNULATION] ' . $motifAnnulation);
        }

        $this->entityManager->flush();

        return $affectation;
    }

    /**
     * Enregistre une cessation d'activité pour un agent
     */
    public function enregistrerCessation(
        Employe $employe,
        \DateTimeInterface $dateEffet,
        string $motif,
        ?string $referenceActe = null,
        string $nouvelleSituationAdmin = Employe::SITUATION_RETRAITE
    ): Affectation {
        $affectation = $this->creerMouvement(
            employe: $employe,
            typeMouvement: Affectation::TYPE_CESSATION,
            dateEffet: $dateEffet,
            structure: null,
            service: null,
            poste: null,
            fonction: null,
            dateDecision: new \DateTime(),
            datePriseService: $dateEffet,
            referenceActe: $referenceActe,
            motif: $motif,
            commentaire: 'Cessation définitive de fonction : ' . $motif,
            autoValiderEtActiver: true
        );

        $employe->setSituationAdministrative($nouvelleSituationAdmin);
        $this->entityManager->flush();

        return $affectation;
    }

    /**
     * Active l'affectation, clôture les précédentes et synchronise l'employé et le poste
     */
    private function activerAffectation(Affectation $affectation, \DateTimeInterface $datePriseService): void
    {
        $employe = $affectation->getEmploye();
        if (!$employe) {
            throw new \LogicException('Affectation sans employé.');
        }

        // 1. Clôturer l'affectation active précédente de l'agent
        $ancienneAffectation = $this->affectationRepository->findActiveByEmploye($employe);
        if ($ancienneAffectation && $ancienneAffectation->getId() !== $affectation->getId()) {
            $ancienneAffectation->setStatut(Affectation::STATUT_CLOTUREE);
            $ancienneAffectation->setDateFin($datePriseService);
            $ancienneAffectation->setUpdatedAt(new \DateTime());
        }

        // 2. Mettre à jour l'affectation
        $affectation->setStatut(Affectation::STATUT_ACTIVE);
        $affectation->setDatePriseService($datePriseService);
        $affectation->setUpdatedAt(new \DateTime());

        // 3. Mettre à jour les entités de destination
        if ($affectation->getTypeMouvement() === Affectation::TYPE_CESSATION) {
            // Libérer le poste actuel si l'agent en occupait un
            if ($employe->getPoste()) {
                $this->posteService->libererPoste(
                    $employe->getPoste(),
                    $datePriseService,
                    $affectation->getMotif() ?? 'Cessation d\'activité'
                );
            }
            $employe->setPoste(null);
            $employe->setStructure(null);
            $employe->setService(null);
            $employe->setFonction(null);
        } else {
            // Mettre à jour les structures
            if ($affectation->getStructure()) {
                $employe->setStructure($affectation->getStructure());
            }
            if ($affectation->getService()) {
                $employe->setService($affectation->getService());
            }
            if ($affectation->getFonction()) {
                $employe->setFonction($affectation->getFonction());
            }

            // Gérer le poste
            if ($affectation->getPoste()) {
                // Utiliser PosteService pour garantir l'atomicité et l'historique d'occupation
                $this->posteService->affecterAgent(
                    $affectation->getPoste(),
                    $employe,
                    $datePriseService,
                    $affectation->getMotif() ?? $affectation->getTypeMouvement(),
                    $affectation->getCommentaire()
                );
            } else {
                // Si l'affectation n'a pas de poste explicite mais que l'agent avait un poste différent
                // on libère l'ancien poste si le mouvement implique un départ
                if ($employe->getPoste() && $affectation->getTypeMouvement() === Affectation::TYPE_DETACHEMENT) {
                    $this->posteService->libererPoste($employe->getPoste(), $datePriseService, 'Détachement');
                    $employe->setPoste(null);
                    $employe->setSituationAdministrative(Employe::SITUATION_DETACHEMENT);
                }
            }
        }

        $this->entityManager->persist($affectation);
        $this->entityManager->flush();
    }

    /**
     * Construit la timeline chronologique complète de la carrière d'un agent
     */
    public function getCarriereTimeline(Employe $employe): array
    {
        $affectations = $this->affectationRepository->findHistoryByEmploye($employe);
        $timeline = [];

        // Événement initial : Prise de service initiale / Recrutement
        if ($employe->getDatePriseService()) {
            $timeline[] = [
                'type' => 'RECRUTEMENT',
                'date' => $employe->getDatePriseService(),
                'titre' => 'Prise de service initiale / Recrutement',
                'description' => sprintf(
                    'Entrée en fonction en tant que %s (%s)',
                    $employe->getStatutAdministratif() ?? 'Agent',
                    $employe->getCorps() ?? 'Corps non spécifié'
                ),
                'badge' => 'primary',
                'icon' => 'fa-user-check',
                'reference' => $employe->getMatricule(),
            ];
        }

        // Mouvements et affectations
        foreach ($affectations as $aff) {
            $dest = $aff->getPoste()?->getIntitule() ?? $aff->getFonction()?->getNom() ?? 'Affectation générale';
            $struct = $aff->getStructure()?->getNom() ?? $aff->getService()?->getNom() ?? 'Structure non spécifiée';

            $badgeColor = match ($aff->getStatut()) {
                Affectation::STATUT_ACTIVE => 'success',
                Affectation::STATUT_VALIDEE => 'info',
                Affectation::STATUT_PROPOSEE => 'warning',
                Affectation::STATUT_CLOTUREE => 'secondary',
                Affectation::STATUT_ANNULEE => 'danger',
                default => 'light',
            };

            $icon = match ($aff->getTypeMouvement()) {
                Affectation::TYPE_AFFECTATION_INITIALE => 'fa-id-badge',
                Affectation::TYPE_MUTATION => 'fa-exchange-alt',
                Affectation::TYPE_PROMOTION => 'fa-level-up-alt',
                Affectation::TYPE_CHANGEMENT_POSTE => 'fa-briefcase',
                Affectation::TYPE_DETACHEMENT => 'fa-external-link-alt',
                Affectation::TYPE_REINTEGRATION => 'fa-undo',
                Affectation::TYPE_CESSATION => 'fa-user-times',
                default => 'fa-history',
            };

            $timeline[] = [
                'type' => $aff->getTypeMouvement(),
                'date' => $aff->getDatePriseService() ?? $aff->getDateEffet(),
                'titre' => $this->getLibelleMouvement($aff->getTypeMouvement()) . ' - ' . $dest,
                'description' => sprintf(
                    'Structure : %s | Acte : %s | Motif : %s',
                    $struct,
                    $aff->getReferenceActe() ?? 'N/A',
                    $aff->getMotif() ?? 'Non précisé'
                ),
                'badge' => $badgeColor,
                'statut_libelle' => $this->getLibelleStatut($aff->getStatut()),
                'icon' => $icon,
                'reference' => $aff->getReference(),
                'affectation' => $aff,
            ];
        }

        return $timeline;
    }

    public function getLibelleMouvement(string $type): string
    {
        return match ($type) {
            Affectation::TYPE_AFFECTATION_INITIALE => 'Affectation initiale',
            Affectation::TYPE_MUTATION => 'Mutation',
            Affectation::TYPE_PROMOTION => 'Promotion',
            Affectation::TYPE_CHANGEMENT_POSTE => 'Changement de poste',
            Affectation::TYPE_DETACHEMENT => 'Détachement',
            Affectation::TYPE_REINTEGRATION => 'Réintégration',
            Affectation::TYPE_CESSATION => 'Cessation d\'activité',
            default => $type,
        };
    }

    public function getLibelleStatut(string $statut): string
    {
        return match ($statut) {
            Affectation::STATUT_PROPOSEE => 'Proposée',
            Affectation::STATUT_VALIDEE => 'Validée',
            Affectation::STATUT_ACTIVE => 'Active (En cours)',
            Affectation::STATUT_CLOTUREE => 'Clôturée (Historique)',
            Affectation::STATUT_ANNULEE => 'Annulée',
            default => $statut,
        };
    }
}
