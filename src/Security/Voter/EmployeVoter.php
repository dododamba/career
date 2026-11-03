<?php

namespace App\Security\Voter;

use App\Entity\Employe;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EmployeVoter extends Voter
{
    public const VIEW = 'EMPLOYE_VIEW';
    public const CREATE = 'EMPLOYE_CREATE';
    public const EDIT = 'EMPLOYE_EDIT';
    public const DELETE = 'EMPLOYE_DELETE';
    public const EXPORT = 'EMPLOYE_EXPORT';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::CREATE, self::EXPORT], true) && ($subject === null || $subject === Employe::class)) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::EXPORT], true)
            && $subject instanceof Employe;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        // Super Admin has unrestricted access across all tenants
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // All non-superadmin operations require an active institution
        $userInstitution = $currentUser->getInstitution();
        if ($userInstitution === null) {
            return false;
        }

        // General creation / export check (no specific instance)
        if ($subject === null || $subject === Employe::class) {
            return match ($attribute) {
                self::CREATE => $this->security->isGranted('ROLE_ADMIN'),
                self::EXPORT => $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MANAGER'),
                default => false,
            };
        }

        /** @var Employe $employe */
        $employe = $subject;
        $employeInstitution = $employe->getInstitution();

        // FAIL CLOSED: If employee has no institution or different institution, strictly deny
        if ($employeInstitution === null || $employeInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($currentUser, $employe),
            self::EDIT => $this->canEdit($currentUser, $employe),
            self::DELETE => $this->canDelete($currentUser, $employe),
            self::EXPORT => $this->canExport($currentUser, $employe),
            default => false,
        };
    }

    private function canView(User $currentUser, Employe $employe): bool
    {
        // Own profile
        if ($employe->getUser() && $employe->getUser()->getId() === $currentUser->getId()) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_MANAGER')
            || $this->security->isGranted('ROLE_USER');
    }

    private function canEdit(User $currentUser, Employe $employe): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canDelete(User $currentUser, Employe $employe): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canExport(User $currentUser, Employe $employe): bool
    {
        return $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MANAGER');
    }
}
