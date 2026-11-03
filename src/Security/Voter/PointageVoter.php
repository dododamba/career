<?php

namespace App\Security\Voter;

use App\Entity\Pointage;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PointageVoter extends Voter
{
    public const VIEW = 'POINTAGE_VIEW';
    public const CREATE = 'POINTAGE_CREATE';
    public const EDIT = 'POINTAGE_EDIT';
    public const DELETE = 'POINTAGE_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE && ($subject === null || $subject === Pointage::class)) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Pointage;
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

        if ($subject === null || $subject === Pointage::class) {
            return true;
        }

        /** @var Pointage $pointage */
        $pointage = $subject;
        $pointageInstitution = $pointage->getInstitution() ?? $pointage->getEmploye()?->getInstitution();

        // FAIL CLOSED
        if ($pointageInstitution === null || $pointageInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT, self::DELETE => $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MANAGER'),
            default => false,
        };
    }
}
