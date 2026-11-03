<?php

namespace App\Security\Voter;

use App\Entity\Employe;
use App\Entity\Interim;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InterimVoter extends Voter
{
    public const VIEW = 'INTERIM_VIEW';
    public const EDIT = 'INTERIM_EDIT';
    public const SUBMIT = 'INTERIM_SUBMIT';
    public const VALIDATE = 'INTERIM_VALIDATE';
    public const REJECT = 'INTERIM_REJECT';
    public const REVOKE = 'INTERIM_REVOKE';
    public const CLOSE = 'INTERIM_CLOSE';
    public const DELETE = 'INTERIM_DELETE';

    public function __construct(
        private Security $security
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::SUBMIT,
            self::VALIDATE,
            self::REJECT,
            self::REVOKE,
            self::CLOSE,
            self::DELETE,
        ], true) && $subject instanceof Interim;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Interim $interim */
        $interim = $subject;

        // 1. Contrôle Multi-Tenant strict (P0)
        $userInstitution = $user->getInstitution();
        $interimInstitution = $interim->getInstitution();

        if (!$userInstitution || !$interimInstitution || $userInstitution->getId() !== $interimInstitution->getId()) {
            return false;
        }

        // 2. Détermination de l'implication de l'utilisateur
        $isTitulaire = false;
        $isInterimaire = false;

        $userEmploye = $user->getEmploye();
        if ($userEmploye instanceof Employe) {
            $isTitulaire = $interim->getTitulaire() && $interim->getTitulaire()->getId() === $userEmploye->getId();
            $isInterimaire = $interim->getInterimaire() && $interim->getInterimaire()->getId() === $userEmploye->getId();
        }

        if (!$isTitulaire && $interim->getTitulaire() && $interim->getTitulaire()->getUser()) {
            $isTitulaire = $interim->getTitulaire()->getUser()->getId() === $user->getId();
        }

        if (!$isInterimaire && $interim->getInterimaire() && $interim->getInterimaire()->getUser()) {
            $isInterimaire = $interim->getInterimaire()->getUser()->getId() === $user->getId();
        }

        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $isManager = $this->security->isGranted('ROLE_MANAGER');

        return match ($attribute) {
            self::VIEW => $this->canView($interim, $isAdmin, $isManager, $isTitulaire, $isInterimaire),
            self::EDIT => $this->canEdit($interim, $isAdmin, $isManager),
            self::SUBMIT => $this->canSubmit($interim, $isAdmin, $isManager, $isTitulaire),
            self::VALIDATE => $this->canValidate($interim, $isAdmin, $isManager, $isTitulaire, $isInterimaire),
            self::REJECT => $this->canReject($interim, $isAdmin, $isManager, $isTitulaire, $isInterimaire),
            self::REVOKE => $this->canRevoke($interim, $isAdmin, $isManager, $isInterimaire),
            self::CLOSE => $this->canClose($interim, $isAdmin, $isManager, $isInterimaire),
            self::DELETE => $this->canDelete($interim, $isAdmin),
            default => false,
        };
    }

    private function canView(Interim $interim, bool $isAdmin, bool $isManager, bool $isTitulaire, bool $isInterimaire): bool
    {
        // Les admins, managers, le titulaire et l'intérimaire peuvent visualiser
        return $isAdmin || $isManager || $isTitulaire || $isInterimaire;
    }

    private function canEdit(Interim $interim, bool $isAdmin, bool $isManager): bool
    {
        if (!$isAdmin && !$isManager) {
            return false;
        }

        return in_array($interim->getStatut(), [Interim::STATUT_BROUILLON, Interim::STATUT_SOUMIS], true);
    }

    private function canSubmit(Interim $interim, bool $isAdmin, bool $isManager, bool $isTitulaire): bool
    {
        if ($interim->getStatut() !== Interim::STATUT_BROUILLON) {
            return false;
        }

        return $isAdmin || $isManager || $isTitulaire;
    }

    private function canValidate(Interim $interim, bool $isAdmin, bool $isManager, bool $isTitulaire, bool $isInterimaire): bool
    {
        // Séparation des tâches : ni le titulaire ni l'intérimaire ne peuvent valider
        if ($isTitulaire || $isInterimaire) {
            return false;
        }

        if (!$isAdmin && !$isManager) {
            return false;
        }

        return in_array($interim->getStatut(), [Interim::STATUT_SOUMIS, Interim::STATUT_BROUILLON], true);
    }

    private function canReject(Interim $interim, bool $isAdmin, bool $isManager, bool $isTitulaire, bool $isInterimaire): bool
    {
        // Séparation des tâches
        if ($isTitulaire || $isInterimaire) {
            return false;
        }

        if (!$isAdmin && !$isManager) {
            return false;
        }

        return $interim->getStatut() === Interim::STATUT_SOUMIS;
    }

    private function canRevoke(Interim $interim, bool $isAdmin, bool $isManager, bool $isInterimaire): bool
    {
        if ($isInterimaire) {
            return false; // L'intérimaire ne révoque pas lui-même
        }

        if (!$isAdmin && !$isManager) {
            return false;
        }

        return $interim->getStatut() === Interim::STATUT_ACTIF;
    }

    private function canClose(Interim $interim, bool $isAdmin, bool $isManager, bool $isInterimaire): bool
    {
        if ($isInterimaire) {
            return false;
        }

        if (!$isAdmin && !$isManager) {
            return false;
        }

        return in_array($interim->getStatut(), [Interim::STATUT_ACTIF, Interim::STATUT_SOUMIS], true);
    }

    private function canDelete(Interim $interim, bool $isAdmin): bool
    {
        if (!$isAdmin) {
            return false;
        }

        // Seul un brouillon ou refusé peut être supprimé
        return in_array($interim->getStatut(), [Interim::STATUT_BROUILLON, Interim::STATUT_REFUSE], true);
    }
}
