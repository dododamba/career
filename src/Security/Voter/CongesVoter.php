<?php

namespace App\Security\Voter;

use App\Entity\Conges;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CongesVoter extends Voter
{
    public const VIEW = 'CONGES_VIEW';
    public const EDIT = 'CONGES_EDIT';
    public const APPROVE = 'CONGES_APPROVE';
    public const REJECT = 'CONGES_REJECT';
    public const CANCEL = 'CONGES_CANCEL';
    public const DELETE = 'CONGES_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::APPROVE, self::REJECT, self::CANCEL, self::DELETE], true)
            && $subject instanceof Conges;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var Conges $conge */
        $conge = $subject;

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Multi-tenant isolation: fail closed if institution mismatch
        $userInstitution = $currentUser->getInstitution();
        if (!$userInstitution) {
            return false;
        }

        $congeInstitution = $conge->getInstitution() ?? $conge->getEmploye()?->getInstitution();
        if ($congeInstitution && $congeInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($currentUser, $conge),
            self::EDIT => $this->canEdit($currentUser, $conge),
            self::APPROVE => $this->canApprove($currentUser, $conge),
            self::REJECT => $this->canReject($currentUser, $conge),
            self::CANCEL => $this->canCancel($currentUser, $conge),
            self::DELETE => $this->canDelete($currentUser, $conge),
            default => false,
        };
    }

    private function isOwner(User $currentUser, Conges $conge): bool
    {
        $employe = $conge->getEmploye();
        if (!$employe) {
            return false;
        }

        if ($employe->getUser() && $employe->getUser()->getId() === $currentUser->getId()) {
            return true;
        }

        if ($currentUser->getEmploye() && $currentUser->getEmploye()->getId() === $employe->getId()) {
            return true;
        }

        return false;
    }

    private function canView(User $currentUser, Conges $conge): bool
    {
        if ($this->isOwner($currentUser, $conge)) {
            return true;
        }

        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canEdit(User $currentUser, Conges $conge): bool
    {
        // Only pending leave requests can be edited
        if ($conge->getStatut() !== Conges::STATUT_EN_ATTENTE) {
            return false;
        }

        if ($this->isOwner($currentUser, $conge)) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canApprove(User $currentUser, Conges $conge): bool
    {
        // Must be in pending state
        if ($conge->getStatut() !== Conges::STATUT_EN_ATTENTE) {
            return false;
        }

        // Must be a manager or admin
        if (!$this->security->isGranted('ROLE_MANAGER') && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        // Cannot approve own leave request (CRITICAL SEPARATION OF CONCERNS)
        if ($this->isOwner($currentUser, $conge)) {
            return false;
        }

        return true;
    }

    private function canReject(User $currentUser, Conges $conge): bool
    {
        // Must be in pending state
        if ($conge->getStatut() !== Conges::STATUT_EN_ATTENTE) {
            return false;
        }

        // Must be a manager or admin
        if (!$this->security->isGranted('ROLE_MANAGER') && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        // Cannot reject own leave request
        if ($this->isOwner($currentUser, $conge)) {
            return false;
        }

        return true;
    }

    private function canCancel(User $currentUser, Conges $conge): bool
    {
        // Can only cancel pending or approved leaves
        if ($conge->getStatut() !== Conges::STATUT_EN_ATTENTE && $conge->getStatut() !== Conges::STATUT_APPROUVE) {
            return false;
        }

        // Owner can cancel if pending
        if ($conge->getStatut() === Conges::STATUT_EN_ATTENTE && $this->isOwner($currentUser, $conge)) {
            return true;
        }

        // Managers and Admins can cancel even approved leaves
        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canDelete(User $currentUser, Conges $conge): bool
    {
        // Only pending leave requests can be deleted
        if ($conge->getStatut() !== Conges::STATUT_EN_ATTENTE) {
            return false;
        }

        if ($this->isOwner($currentUser, $conge)) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN');
    }
}
