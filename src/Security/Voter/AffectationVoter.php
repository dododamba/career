<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AffectationVoter extends Voter
{
    public const VIEW = 'AFFECTATION_VIEW';
    public const CREATE = 'AFFECTATION_CREATE';
    public const EDIT = 'AFFECTATION_EDIT';
    public const VALIDATE = 'AFFECTATION_VALIDATE';
    public const PRISE_SERVICE = 'AFFECTATION_PRISE_SERVICE';
    public const CANCEL = 'AFFECTATION_CANCEL';
    public const DELETE = 'AFFECTATION_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE && ($subject === null || $subject === Affectation::class)) {
            return true;
        }

        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::VALIDATE,
            self::PRISE_SERVICE,
            self::CANCEL,
            self::DELETE,
        ], true) && $subject instanceof Affectation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        // Super Admin a accès universel
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        $userInstitution = $currentUser->getInstitution();
        if ($userInstitution === null) {
            return false;
        }

        if ($subject === null || $subject === Affectation::class) {
            return match ($attribute) {
                self::CREATE => $this->security->isGranted('ROLE_ADMIN'),
                default => false,
            };
        }

        /** @var Affectation $affectation */
        $affectation = $subject;
        $affInstitution = $affectation->getInstitution();

        // Isolation multi-tenant stricte (fail-closed)
        if ($affInstitution === null || $affInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->security->isGranted('ROLE_USER'),
            self::EDIT => $this->security->isGranted('ROLE_ADMIN') && in_array($affectation->getStatut(), [Affectation::STATUT_PROPOSEE, Affectation::STATUT_VALIDEE], true),
            self::VALIDATE => $this->security->isGranted('ROLE_ADMIN') && $affectation->getStatut() === Affectation::STATUT_PROPOSEE,
            self::PRISE_SERVICE => $this->security->isGranted('ROLE_ADMIN') && in_array($affectation->getStatut(), [Affectation::STATUT_PROPOSEE, Affectation::STATUT_VALIDEE], true),
            self::CANCEL => $this->security->isGranted('ROLE_ADMIN') && in_array($affectation->getStatut(), [Affectation::STATUT_PROPOSEE, Affectation::STATUT_VALIDEE], true),
            self::DELETE => $this->security->isGranted('ROLE_ADMIN') && in_array($affectation->getStatut(), [Affectation::STATUT_PROPOSEE, Affectation::STATUT_ANNULEE], true),
            default => false,
        };
    }
}
