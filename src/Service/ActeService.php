<?php

namespace App\Service;

use App\Entity\Acte;
use App\Entity\Affectation;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Poste;
use App\Entity\Structure;
use App\Entity\TypeActe;
use App\Entity\User;
use App\Repository\ActeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActeRepository $acteRepository
    ) {
    }

    /**
     * Crée un nouvel acte administratif avec contrôle strict d'isolation multi-tenant (anti-IDOR).
     */
    public function creerActe(
        Institution $institution,
        TypeActe $typeActe,
        string $numero,
        \DateTimeInterface $dateSignature,
        \DateTimeInterface $dateEffet,
        string $objet,
        string $signataireQualite,
        ?string $signataireNom = null,
        string $statut = Acte::STATUT_SIGNE,
        ?\DateTimeInterface $datePublication = null,
        ?string $contenuResume = null,
        ?Employe $employe = null,
        ?Poste $poste = null,
        ?Structure $structure = null,
        ?Affectation $affectation = null,
        ?Document $document = null,
        ?Acte $acteParent = null,
        ?User $currentUser = null
    ): Acte {
        // 1. Contrôle anti-IDOR sur le type d'acte (doit être global ou appartenir au tenant)
        if ($typeActe->getInstitution() !== null && $typeActe->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Type d\'acte invalide pour cette institution.');
        }

        // 2. Contrôle anti-IDOR sur les entités liées
        $this->validerEntitesTenant($institution, $employe, $poste, $structure, $affectation, $document, $acteParent);

        // 3. Instanciation et hydratation
        $acte = new Acte();
        $acte->setInstitution($institution)
            ->setTypeActe($typeActe)
            ->setNumero(trim($numero))
            ->setDateSignature($dateSignature)
            ->setDateEffet($dateEffet)
            ->setDatePublication($datePublication)
            ->setObjet(trim($objet))
            ->setSignataireQualite(trim($signataireQualite))
            ->setSignataireNom($signataireNom ? trim($signataireNom) : null)
            ->setStatut($statut)
            ->setContenuResume($contenuResume)
            ->setEmploye($employe)
            ->setPoste($poste)
            ->setStructure($structure)
            ->setAffectation($affectation)
            ->setDocument($document)
            ->setActeParent($acteParent)
            ->setCreePar($currentUser)
            ->setCreatedAt(new \DateTime());

        // Si une affectation est fournie sans agent/structure/poste explicite, déduire automatiquement
        if ($affectation !== null) {
            if ($acte->getEmploye() === null) {
                $acte->setEmploye($affectation->getEmploye());
            }
            if ($acte->getStructure() === null) {
                $acte->setStructure($affectation->getStructure());
            }
            if ($acte->getPoste() === null) {
                $acte->setPoste($affectation->getPoste());
            }
        }

        // Si l'acte modifie ou abroge un acte parent, mettre à jour le statut du parent
        if ($acteParent !== null) {
            if ($typeActe->getCode() === TypeActe::CODE_ARRETE || $typeActe->getCode() === TypeActe::CODE_DECISION) {
                // Si l'objet mentionne une abrogation, passer le parent en statut ABROGE
                if (stripos($objet, 'abrog') !== false) {
                    $acteParent->setStatut(Acte::STATUT_ABROGE);
                } else {
                    $acteParent->setStatut(Acte::STATUT_MODIFIE);
                }
            }
        }

        $this->entityManager->persist($acte);
        $this->entityManager->flush();

        return $acte;
    }

    /**
     * Met à jour un acte existant avec vérification anti-IDOR.
     */
    public function modifierActe(
        Acte $acte,
        TypeActe $typeActe,
        string $numero,
        \DateTimeInterface $dateSignature,
        \DateTimeInterface $dateEffet,
        string $objet,
        string $signataireQualite,
        ?string $signataireNom = null,
        string $statut = Acte::STATUT_SIGNE,
        ?\DateTimeInterface $datePublication = null,
        ?string $contenuResume = null,
        ?Employe $employe = null,
        ?Poste $poste = null,
        ?Structure $structure = null,
        ?Affectation $affectation = null,
        ?Document $document = null,
        ?Acte $acteParent = null
    ): Acte {
        $institution = $acte->getInstitution();

        // 1. Contrôle anti-IDOR sur le type d'acte
        if ($typeActe->getInstitution() !== null && $typeActe->getInstitution()->getId() !== $institution->getId()) {
            throw new AccessDeniedException('Type d\'acte invalide pour cette institution.');
        }

        // 2. Contrôle anti-IDOR sur les entités liées
        $this->validerEntitesTenant($institution, $employe, $poste, $structure, $affectation, $document, $acteParent);

        // 3. Mise à jour
        $acte->setTypeActe($typeActe)
            ->setNumero(trim($numero))
            ->setDateSignature($dateSignature)
            ->setDateEffet($dateEffet)
            ->setDatePublication($datePublication)
            ->setObjet(trim($objet))
            ->setSignataireQualite(trim($signataireQualite))
            ->setSignataireNom($signataireNom ? trim($signataireNom) : null)
            ->setStatut($statut)
            ->setContenuResume($contenuResume)
            ->setEmploye($employe)
            ->setPoste($poste)
            ->setStructure($structure)
            ->setAffectation($affectation)
            ->setDocument($document)
            ->setActeParent($acteParent)
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $acte;
    }

    /**
     * Fait évoluer le statut juridique de l'acte (Cycle de vie administratif).
     */
    public function changerStatut(Acte $acte, string $nouveauStatut, ?\DateTimeInterface $datePublication = null): Acte
    {
        $validStatuses = [
            Acte::STATUT_PROJET,
            Acte::STATUT_SIGNE,
            Acte::STATUT_NOTIFIE,
            Acte::STATUT_MODIFIE,
            Acte::STATUT_ABROGE,
            Acte::STATUT_ANNULE,
        ];

        if (!in_array($nouveauStatut, $validStatuses, true)) {
            throw new \InvalidArgumentException(sprintf('Statut d\'acte invalide : %s', $nouveauStatut));
        }

        $acte->setStatut($nouveauStatut);
        $acte->setUpdatedAt(new \DateTime());

        if ($nouveauStatut === Acte::STATUT_NOTIFIE) {
            $acte->setDatePublication($datePublication ?? new \DateTime());
        }

        $this->entityManager->flush();

        return $acte;
    }

    /**
     * Attache un document numérisé (Module 07) à l'acte.
     */
    public function attacherDocument(Acte $acte, Document $document): Acte
    {
        if ($document->getInstitution()->getId() !== $acte->getInstitution()->getId()) {
            throw new AccessDeniedException('Le document n\'appartient pas à la même institution.');
        }

        $acte->setDocument($document);
        $acte->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return $acte;
    }

    /**
     * Dissocie le document numérisé de l'acte.
     */
    public function dissocierDocument(Acte $acte): Acte
    {
        $acte->setDocument(null);
        $acte->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return $acte;
    }

    /**
     * Associe l'acte à une affectation de carrière (Module 06).
     */
    public function associerAffectation(Acte $acte, Affectation $affectation): Acte
    {
        if ($affectation->getInstitution()->getId() !== $acte->getInstitution()->getId()) {
            throw new AccessDeniedException('L\'affectation n\'appartient pas à la même institution.');
        }

        $acte->setAffectation($affectation);
        if ($acte->getEmploye() === null) {
            $acte->setEmploye($affectation->getEmploye());
        }
        if ($acte->getStructure() === null) {
            $acte->setStructure($affectation->getStructure());
        }
        if ($acte->getPoste() === null) {
            $acte->setPoste($affectation->getPoste());
        }

        $acte->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return $acte;
    }

    /**
     * Supprime un acte administratif.
     */
    public function supprimerActe(Acte $acte): void
    {
        $this->entityManager->remove($acte);
        $this->entityManager->flush();
    }

    /**
     * Vérification stricte anti-IDOR des entités associées.
     */
    private function validerEntitesTenant(
        Institution $institution,
        ?Employe $employe,
        ?Poste $poste,
        ?Structure $structure,
        ?Affectation $affectation,
        ?Document $document,
        ?Acte $acteParent
    ): void {
        $instId = $institution->getId();

        if ($employe !== null && $employe->getInstitution()->getId() !== $instId) {
            throw new AccessDeniedException('L\'employé sélectionné n\'appartient pas à cette institution.');
        }

        if ($poste !== null && $poste->getInstitution()->getId() !== $instId) {
            throw new AccessDeniedException('Le poste sélectionné n\'appartient pas à cette institution.');
        }

        if ($structure !== null && $structure->getInstitution()->getId() !== $instId) {
            throw new AccessDeniedException('La structure sélectionnée n\'appartient pas à cette institution.');
        }

        if ($affectation !== null && $affectation->getInstitution()->getId() !== $instId) {
            throw new AccessDeniedException('L\'affectation sélectionnée n\'appartient pas à cette institution.');
        }

        if ($document !== null && $document->getInstitution()->getId() !== $instId) {
            throw new AccessDeniedException('Le document sélectionné n\'appartient pas à cette institution.');
        }

        if ($acteParent !== null && $acteParent->getInstitution()->getId() !== $instId) {
            throw new AccessDeniedException('L\'acte parent sélectionné n\'appartient pas à cette institution.');
        }
    }
}
