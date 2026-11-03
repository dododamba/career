<?php

namespace App\Security\Voter;

use App\Entity\Absence;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AbsenceVoter extends Voter
{
    public const VIEW = 'ABSENCE_VIEW';
    public const EDIT = 'ABSENCE_EDIT';
    public const APPROVE = 'ABSENCE_APPROVE';
    public const REJECT = 'ABSENCE_REJECT';
    public const CANCEL = 'ABSENCE_CANCEL';
    public const DELETE = 'ABSENCE_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::APPROVE, self::REJECT, self::CANCEL, self::DELETE], true)
            && $subject instanceof Absence;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var Absence $absence */
        $absence = $subject;

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Multi-tenant isolation: fail closed if institution mismatch
        $userInstitution = $currentUser->getInstitution();
        if (!$userInstitution) {
            return false;
        }

        $absenceInstitution = $absence->getInstitution() ?? $absence->getEmploye()?->getInstitution();
        if ($absenceInstitution && $absenceInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($currentUser, $absence),
            self::EDIT => $this->canEdit($currentUser, $absence),
            self::APPROVE => $this->canApprove($currentUser, $absence),
            self::REJECT => $this->canReject($currentUser, $absence),
            self::CANCEL => $this->canCancel($currentUser, $absence),
            self::DELETE => $this->canDelete($currentUser, $absence),
            default => false,
        };
    }

    private function isOwner(User $currentUser, Absence $absence): bool
    {
        $employe = $absence->getEmploye();
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

    private function canView(User $currentUser, Absence $absence): bool
    {
        if ($this->isOwner($currentUser, $absence)) {
            return true;
        }

        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canEdit(User $currentUser, Absence $absence): bool
    {
        // Only pending absence requests can be edited
        if ($absence->getStatut() !== Absence::STATUT_EN_ATTENTE) {
            return false;
        }

        if ($this->isOwner($currentUser, $absence)) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canApprove(User $currentUser, Absence $absence): bool
    {
        // Must be in pending state
        if ($absence->getStatut() !== Absence::STATUT_EN_ATTENTE) {
            return false;
        }

        // Must be a manager or admin
        if (!$this->security->isGranted('ROLE_MANAGER') && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        // Anti-self-approval
        if ($this->isOwner($currentUser, $absence)) {
            return false;
        }

        return true;
    }

    private function canReject(User $currentUser, Absence $absence): bool
    {
        // Must be in pending state
        if ($absence->getStatut() !== Absence::STATUT_EN_ATTENTE) {
            return false;
        }

        // Must be a manager or admin
        if (!$this->security->isGranted('ROLE_MANAGER') && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        // Anti-self-rejection
        if ($this->isOwner($currentUser, $absence)) {
            return false;
        }

        return true;
    }

    private function canCancel(User $currentUser, Absence $absence): bool
    {
        // Can only cancel pending or approved absences
        if ($absence->getStatut() !== Absence::STATUT_EN_ATTENTE && $absence->getStatut() !== Absence::STATUT_APPROUVE) {
            return false;
        }

        // Owner can cancel if pending
        if ($absence->getStatut() === Absence::STATUT_EN_ATTENTE && $this->isOwner($currentUser, $absence)) {
            return true;
        }

        // Managers and Admins can cancel even approved absences
        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canDelete(User $currentUser, Absence $absence): bool
    {
        // Only pending absence requests can be deleted
        if ($absence->getStatut() !== Absence::STATUT_EN_ATTENTE) {
            return false;
        }

        if ($this->isOwner($currentUser, $absence)) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN');
    }
}
