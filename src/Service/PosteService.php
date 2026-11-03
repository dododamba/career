<?php

namespace App\Service;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\OccupationPoste;
use App\Entity\Poste;
use App\Repository\OccupationPosteRepository;
use App\Repository\PosteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PosteService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PosteRepository $posteRepository,
        private OccupationPosteRepository $occupationPosteRepository
    ) {
    }

    /**
     * Affecte un agent à un poste avec traçabilité complète et atomicité
     */
    public function affecterAgent(
        Poste $poste,
        Employe $employe,
        \DateTimeInterface $dateDebut,
        string $motif = OccupationPoste::MOTIF_RECRUTEMENT,
        ?string $commentaire = null
    ): OccupationPoste {
        // Sécurité multi-tenant : l'agent et le poste doivent appartenir à la même institution
        $posteInst = $poste->getInstitution();
        $employeInst = $employe->getInstitution();

        if (!$posteInst || !$employeInst || $posteInst->getId() !== $employeInst->getId()) {
            throw new AccessDeniedException('Violation d\'isolation multi-tenant : l\'agent et le poste appartiennent à des institutions distinctes.');
        }

        // 1. Clôturer l'occupation active précédente de ce poste s'il y en avait une
        $currentPosteOcc = $this->occupationPosteRepository->findCurrentOccupation($poste);
        if ($currentPosteOcc !== null) {
            $currentPosteOcc->setDateFin($dateDebut);
            $currentPosteOcc->setIsActif(false);
            if ($currentPosteOcc->getEmploye() && $currentPosteOcc->getEmploye()->getPoste() === $poste) {
                $currentPosteOcc->getEmploye()->setPoste(null);
            }
        }

        // 2. Clôturer l'éventuelle occupation active de l'employé sur un autre poste
        $anciensPostesAgent = $this->occupationPosteRepository->findBy([
            'employe' => $employe,
            'is_actif' => true,
            'date_fin' => null,
        ]);
        foreach ($anciensPostesAgent as $ancOcc) {
            $ancOcc->setDateFin($dateDebut);
            $ancOcc->setIsActif(false);
            if ($ancOcc->getPoste() && $ancOcc->getPoste()->getId() !== $poste->getId()) {
                $ancPoste = $ancOcc->getPoste();
                $ancPoste->setOccupantActuel(null);
                $ancPoste->setStatut(Poste::STATUT_VACANT);
                $ancPoste->setUpdatedAt(new \DateTime());
            }
        }

        // 3. Mettre à jour le poste
        $poste->setOccupantActuel($employe);
        $poste->setStatut(Poste::STATUT_OCCUPE);
        $poste->setUpdatedAt(new \DateTime());

        // 4. Mettre à jour l'employé
        $employe->setPoste($poste);
        if ($poste->getStructure() !== null) {
            $employe->setStructure($poste->getStructure());
        }
        if ($poste->getService() !== null) {
            $employe->setService($poste->getService());
        }
        if ($poste->getFonction() !== null) {
            $employe->setFonction($poste->getFonction());
        }

        // 5. Créer la nouvelle occupation
        $occupation = new OccupationPoste();
        $occupation->setPoste($poste);
        $occupation->setEmploye($employe);
        $occupation->setInstitution($posteInst);
        $occupation->setDateDebut($dateDebut);
        $occupation->setMotif($motif);
        $occupation->setCommentaire($commentaire);
        $occupation->setIsActif(true);

        $this->entityManager->persist($occupation);
        $this->entityManager->flush();

        return $occupation;
    }

    /**
     * Libère un poste en clôturant l'occupation en cours et en remettant le statut à VACANT
     */
    public function libererPoste(Poste $poste, \DateTimeInterface $dateFin, ?string $motifDepart = null): void
    {
        $currentOcc = $this->occupationPosteRepository->findCurrentOccupation($poste);
        if ($currentOcc !== null) {
            $currentOcc->setDateFin($dateFin);
            $currentOcc->setIsActif(false);
            if ($motifDepart) {
                $ancienCom = $currentOcc->getCommentaire();
                $currentOcc->setCommentaire(($ancienCom ? $ancienCom . ' | ' : '') . 'Départ : ' . $motifDepart);
            }
        }

        $ancienOccupant = $poste->getOccupantActuel();
        if ($ancienOccupant !== null && $ancienOccupant->getPoste() === $poste) {
            $ancienOccupant->setPoste(null);
        }

        $poste->setOccupantActuel(null);
        $poste->setStatut(Poste::STATUT_VACANT);
        $poste->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();
    }

    /**
     * Assemble l'ensemble des données pour la Fiche de Poste 360°
     */
    public function getPoste360View(Poste $poste): array
    {
        $historiqueOccupations = $this->occupationPosteRepository->findHistoryByPoste($poste);
        $occupationActuelle = $this->occupationPosteRepository->findCurrentOccupation($poste);

        $statutMeta = match ($poste->getStatut()) {
            Poste::STATUT_VACANT => [
                'badge' => 'bg-warning text-dark',
                'label' => 'Poste Vacant',
                'icon' => 'fa-door-open',
            ],
            Poste::STATUT_OCCUPE => [
                'badge' => 'bg-success',
                'label' => 'Poste Occupé',
                'icon' => 'fa-user-check',
            ],
            Poste::STATUT_SUSPENDU => [
                'badge' => 'bg-secondary',
                'label' => 'Poste Suspendu',
                'icon' => 'fa-pause-circle',
            ],
            Poste::STATUT_FERME => [
                'badge' => 'bg-danger',
                'label' => 'Poste Fermé',
                'icon' => 'fa-times-circle',
            ],
            default => [
                'badge' => 'bg-light text-dark',
                'label' => $poste->getStatut(),
                'icon' => 'fa-tag',
            ],
        };

        $niveauLabel = match ($poste->getNiveau()) {
            Poste::NIVEAU_DIRECTION => 'Direction & Haute Responsabilité',
            Poste::NIVEAU_CADRE_SUPERIEUR => 'Cadre Supérieur',
            Poste::NIVEAU_CADRE => 'Cadre',
            Poste::NIVEAU_MAITRISE => 'Agent de Maîtrise / Technicien',
            Poste::NIVEAU_EXECUTION => 'Agent d\'Exécution',
            default => $poste->getNiveau() ?? 'Cadre',
        };

        // Chemin arborescent
        $structurePath = [];
        if ($poste->getStructure()) {
            $curr = $poste->getStructure();
            while ($curr !== null) {
                array_unshift($structurePath, $curr);
                $curr = $curr->getParent();
            }
        }

        // Durée d'occupation actuelle
        $dureeOccupation = $occupationActuelle ? $occupationActuelle->getDuree() : null;

        return [
            'poste' => $poste,
            'statutMeta' => $statutMeta,
            'niveauLabel' => $niveauLabel,
            'occupantActuel' => $poste->getOccupantActuel(),
            'occupationActuelle' => $occupationActuelle,
            'dureeOccupation' => $dureeOccupation,
            'historiqueOccupations' => $historiqueOccupations,
            'structurePath' => $structurePath,
            'totalOccupantsHistorique' => count($historiqueOccupations),
        ];
    }

    /**
     * Métriques consolidées des postes de l'institution
     */
    public function getInstitutionPostesDashboard(Institution $institution): array
    {
        return $this->posteRepository->getPostesStatsByInstitution($institution);
    }
}
