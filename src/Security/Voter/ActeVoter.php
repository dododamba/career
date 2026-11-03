<?php

namespace App\Security\Voter;

use App\Entity\Acte;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ActeVoter extends Voter
{
    public const VIEW = 'ACTE_VIEW';
    public const CREATE = 'ACTE_CREATE';
    public const EDIT = 'ACTE_EDIT';
    public const SIGN = 'ACTE_SIGN';
    public const NOTIFY = 'ACTE_NOTIFY';
    public const ABROGER = 'ACTE_ABROGER';
    public const DELETE = 'ACTE_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE && ($subject === null || $subject === Acte::class)) {
            return true;
        }

        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::SIGN,
            self::NOTIFY,
            self::ABROGER,
            self::DELETE,
        ], true) && $subject instanceof Acte;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        // Super Admin dispose d'un accès global
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        $userInstitution = $currentUser->getInstitution();
        if ($userInstitution === null) {
            return false;
        }

        if ($subject === null || $subject === Acte::class) {
            return match ($attribute) {
                self::CREATE => $this->security->isGranted('ROLE_ADMIN'),
                default => false,
            };
        }

        /** @var Acte $acte */
        $acte = $subject;
        $acteInstitution = $acte->getInstitution();

        // 1. Isolation multi-tenant stricte (fail-closed)
        if ($acteInstitution === null || $acteInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        // 2. Contrôle d'accès par rôle
        return match ($attribute) {
            self::VIEW => $this->canView($acte, $currentUser),
            self::EDIT, self::SIGN, self::NOTIFY, self::ABROGER, self::DELETE => $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }

    private function canView(Acte $acte, User $currentUser): bool
    {
        // Les administrateurs RH peuvent tout consulter au sein de leur tenant
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Un projet non signé n'est visible que par les administrateurs
        if ($acte->getStatut() === Acte::STATUT_PROJET) {
            return false;
        }

        // Si l'acte concerne directement l'agent connecté
        $employeUser = $acte->getEmploye()?->getUser();
        if ($employeUser && $employeUser->getId() === $currentUser->getId()) {
            return true;
        }

        // Si l'acte est à portée générale (pas d'agent nominatif), accessible aux membres de l'institution
        if ($acte->getEmploye() === null && $this->security->isGranted('ROLE_USER')) {
            return true;
        }

        return false;
    }
}
