<?php

namespace App\Security\Voter;

use App\Entity\Poste;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PosteVoter extends Voter
{
    public const VIEW = 'POSTE_VIEW';
    public const CREATE = 'POSTE_CREATE';
    public const EDIT = 'POSTE_EDIT';
    public const DELETE = 'POSTE_DELETE';
    public const AFFECTER = 'POSTE_AFFECTER';
    public const LIBERER = 'POSTE_LIBERER';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE && ($subject === null || $subject === Poste::class)) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::AFFECTER, self::LIBERER], true)
            && $subject instanceof Poste;
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

        $userInstitution = $currentUser->getInstitution();
        if ($userInstitution === null) {
            return false;
        }

        if ($subject === null || $subject === Poste::class) {
            return match ($attribute) {
                self::CREATE => $this->security->isGranted('ROLE_ADMIN'),
                default => false,
            };
        }

        /** @var Poste $poste */
        $poste = $subject;
        $posteInstitution = $poste->getInstitution();

        // Cross-tenant isolation : strictly deny if different or absent institution
        if ($posteInstitution === null || $posteInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->security->isGranted('ROLE_USER'),
            self::EDIT, self::DELETE, self::AFFECTER, self::LIBERER => $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }
}
