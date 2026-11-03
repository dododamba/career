<?php

namespace App\Security\Voter;

use App\Entity\Structure;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class StructureVoter extends Voter
{
    public const VIEW = 'STRUCTURE_VIEW';
    public const CREATE = 'STRUCTURE_CREATE';
    public const EDIT = 'STRUCTURE_EDIT';
    public const DELETE = 'STRUCTURE_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE && ($subject === null || $subject === Structure::class)) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Structure;
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

        if ($subject === null || $subject === Structure::class) {
            return match ($attribute) {
                self::CREATE => $this->security->isGranted('ROLE_ADMIN'),
                default => false,
            };
        }

        /** @var Structure $structure */
        $structure = $subject;
        $structureInstitution = $structure->getInstitution();

        // Cross-tenant isolation
        if ($structureInstitution === null || $structureInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->security->isGranted('ROLE_USER') || $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN'),
            self::EDIT => $this->security->isGranted('ROLE_ADMIN'),
            self::DELETE => $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }
}
