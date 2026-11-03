<?php

namespace App\Security\Voter;

use App\Entity\Institution;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InstitutionVoter extends Voter
{
    public const VIEW = 'INSTITUTION_VIEW';
    public const EDIT = 'INSTITUTION_EDIT';
    public const DELETE = 'INSTITUTION_DELETE';
    public const SWITCH = 'INSTITUTION_SWITCH';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::SWITCH], true)
            && $subject instanceof Institution;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        // Super Admin has full power across institutions
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        /** @var Institution $institution */
        $institution = $subject;
        $userInstitution = $currentUser->getInstitution();

        if ($userInstitution === null) {
            return false;
        }

        $isSameInstitution = $userInstitution->getId() === $institution->getId();

        return match ($attribute) {
            self::VIEW => $isSameInstitution,
            self::EDIT => $isSameInstitution && $this->security->isGranted('ROLE_ADMIN'),
            self::DELETE => false, // Only super admin can delete an entire institution/tenant
            self::SWITCH => false, // Only super admin can switch tenants
            default => false,
        };
    }
}
