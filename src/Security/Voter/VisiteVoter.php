<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Visite;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VisiteVoter extends Voter
{
    public const VIEW = 'VISITE_VIEW';
    public const CREATE = 'VISITE_CREATE';
    public const EDIT = 'VISITE_EDIT';
    public const DELETE = 'VISITE_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE && ($subject === null || $subject === Visite::class)) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Visite;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        $userInstitution = $currentUser->getInstitution();
        if ($userInstitution === null) {
            return false;
        }

        if ($subject === null || $subject === Visite::class) {
            return true;
        }

        /** @var Visite $visite */
        $visite = $subject;
        $visiteInstitution = $visite->getInstitution() ?? $visite->getEmploye()?->getInstitution();

        if ($visiteInstitution === null || $visiteInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT, self::DELETE => $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MANAGER'),
            default => false,
        };
    }
}
