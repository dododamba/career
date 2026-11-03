<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const VIEW = 'USER_VIEW';
    public const CREATE = 'USER_CREATE';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';
    public const TOGGLE_STATUS = 'USER_TOGGLE_STATUS';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::TOGGLE_STATUS], true)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        // Super admin has full access
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        /** @var User|null $targetUser */
        $targetUser = $subject instanceof User ? $subject : null;

        return match ($attribute) {
            self::VIEW => $this->canView($currentUser, $targetUser),
            self::CREATE => $this->canCreate($currentUser),
            self::EDIT => $this->canEdit($currentUser, $targetUser),
            self::DELETE => $this->canDelete($currentUser, $targetUser),
            self::TOGGLE_STATUS => $this->canToggleStatus($currentUser, $targetUser),
            default => false,
        };
    }

    private function canView(User $currentUser, ?User $targetUser): bool
    {
        if ($targetUser === null) {
            return false;
        }

        // A user can view their own profile
        if ($currentUser->getId() === $targetUser->getId()) {
            return true;
        }

        // Admins can view users in the same institution
        if ($this->security->isGranted('ROLE_ADMIN') && $currentUser->isSameInstitution($targetUser)) {
            return true;
        }

        // Managers can view users in the same institution
        if ($this->security->isGranted('ROLE_MANAGER') && $currentUser->isSameInstitution($targetUser)) {
            return true;
        }

        return false;
    }

    private function canCreate(User $currentUser): bool
    {
        // Only administrators can create new users
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canEdit(User $currentUser, ?User $targetUser): bool
    {
        if ($targetUser === null) {
            return false;
        }

        // Users can edit their own profile (limited fields handled by form)
        if ($currentUser->getId() === $targetUser->getId()) {
            return true;
        }

        // Admins can edit users in their institution
        return $this->security->isGranted('ROLE_ADMIN') && $currentUser->isSameInstitution($targetUser);
    }

    private function canDelete(User $currentUser, ?User $targetUser): bool
    {
        if ($targetUser === null) {
            return false;
        }

        // Cannot delete oneself
        if ($currentUser->getId() === $targetUser->getId()) {
            return false;
        }

        // Admins can delete users in their institution
        return $this->security->isGranted('ROLE_ADMIN') && $currentUser->isSameInstitution($targetUser);
    }

    private function canToggleStatus(User $currentUser, ?User $targetUser): bool
    {
        if ($targetUser === null) {
            return false;
        }

        // Cannot toggle own status (prevent self-lockout)
        if ($currentUser->getId() === $targetUser->getId()) {
            return false;
        }

        // Admins can toggle status in their institution
        return $this->security->isGranted('ROLE_ADMIN') && $currentUser->isSameInstitution($targetUser);
    }
}
