<?php

namespace App\Security\Voter;

use App\Entity\Document;
use App\Entity\DocumentVersion;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DocumentVoter extends Voter
{
    public const VIEW = 'DOCUMENT_VIEW';
    public const DOWNLOAD = 'DOCUMENT_DOWNLOAD';
    public const CREATE = 'DOCUMENT_CREATE';
    public const EDIT = 'DOCUMENT_EDIT';
    public const NEW_VERSION = 'DOCUMENT_NEW_VERSION';
    public const DELETE = 'DOCUMENT_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE && ($subject === null || $subject === Document::class)) {
            return true;
        }

        return in_array($attribute, [
            self::VIEW,
            self::DOWNLOAD,
            self::EDIT,
            self::NEW_VERSION,
            self::DELETE,
        ], true) && ($subject instanceof Document || $subject instanceof DocumentVersion);
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

        if ($subject === null || $subject === Document::class) {
            return match ($attribute) {
                self::CREATE => $this->security->isGranted('ROLE_ADMIN'),
                default => false,
            };
        }

        /** @var Document $document */
        $document = ($subject instanceof DocumentVersion) ? $subject->getDocument() : $subject;
        if (!$document) {
            return false;
        }

        $docInstitution = $document->getInstitution();

        // 1. Cloisonnement multi-tenant strict (fail-closed)
        if ($docInstitution === null || $docInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        // 2. Contrôle d'accès par rôle et confidentialité
        return match ($attribute) {
            self::VIEW, self::DOWNLOAD => $this->canViewOrDownload($document, $currentUser),
            self::EDIT, self::NEW_VERSION, self::DELETE => $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }

    private function canViewOrDownload(Document $document, User $currentUser): bool
    {
        // Les administrateurs RH ont toujours accès
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Si le document est strictement confidentiel RH, refus pour les non-admins
        if ($document->getNiveauConfidentialite() === Document::CONFIDENTIALITE_SECRET_RH) {
            return false;
        }

        // Si le document est confidentiel, accessible si l'utilisateur connecté est l'agent concerné
        if ($document->getNiveauConfidentialite() === Document::CONFIDENTIALITE_CONFIDENTIEL) {
            $employeUser = $document->getEmploye()?->getUser();
            if ($employeUser && $employeUser->getId() === $currentUser->getId()) {
                return true;
            }
            return false;
        }

        // Si public interne, accessible à tout utilisateur de la même institution
        return $this->security->isGranted('ROLE_USER');
    }
}
