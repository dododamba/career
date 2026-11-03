<?php

namespace App\Service;

use App\Entity\SecurityAuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SecurityAuditService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack
    ) {
    }

    public function log(
        string $event,
        string $eventLabel,
        ?string $userIdentifier,
        ?User $user = null,
        ?string $description = null,
        ?Request $request = null
    ): SecurityAuditLog {
        $request = $request ?? $this->requestStack->getCurrentRequest();

        $auditLog = new SecurityAuditLog();
        $auditLog->setEvent($event);
        $auditLog->setEventLabel($eventLabel);
        $auditLog->setUserIdentifier($userIdentifier ?? ($user ? $user->getUserIdentifier() : 'inconnu'));
        $auditLog->setUser($user);
        $auditLog->setDescription($description);

        if ($user === null && $userIdentifier !== null) {
            $foundUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);
            if ($foundUser && $foundUser->getInstitution() !== null) {
                $auditLog->setInstitution($foundUser->getInstitution());
            }
        }

        if ($request) {
            $auditLog->setIpAddress($request->getClientIp());
            $auditLog->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        return $auditLog;
    }

    public function logLoginSuccess(User $user, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_LOGIN_SUCCESS,
            'Connexion réussie',
            $user->getUserIdentifier(),
            $user,
            sprintf('Connexion réussie pour l\'utilisateur %s', $user->getUserIdentifier()),
            $request
        );
    }

    public function logLoginFailure(string $identifier, ?Request $request = null, ?string $reason = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_LOGIN_FAILURE,
            'Échec de connexion',
            $identifier,
            null,
            $reason ? sprintf('Échec de connexion (%s)', $reason) : 'Identifiants invalides ou compte inactif',
            $request
        );
    }

    public function logLogout(User $user, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_LOGOUT,
            'Déconnexion',
            $user->getUserIdentifier(),
            $user,
            sprintf('Déconnexion de l\'utilisateur %s', $user->getUserIdentifier()),
            $request
        );
    }

    public function logPasswordChanged(User $user, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_PASSWORD_CHANGED,
            'Mot de passe modifié',
            $user->getUserIdentifier(),
            $user,
            'Changement du mot de passe effectué',
            $request
        );
    }

    public function logPasswordResetRequested(string $identifier, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_PASSWORD_RESET_REQUESTED,
            'Demande de réinitialisation de mot de passe',
            $identifier,
            null,
            'Demande de lien de réinitialisation soumise',
            $request
        );
    }

    public function logPasswordResetCompleted(User $user, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_PASSWORD_RESET_COMPLETED,
            'Réinitialisation de mot de passe terminée',
            $user->getUserIdentifier(),
            $user,
            'Mot de passe réinitialisé via jeton temporaire',
            $request
        );
    }

    public function logMfaEnabled(User $user, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_MFA_ENABLED,
            'Activation MFA',
            $user->getUserIdentifier(),
            $user,
            'Authentification à deux facteurs TOTP activée',
            $request
        );
    }

    public function logMfaDisabled(User $user, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_MFA_DISABLED,
            'Désactivation MFA',
            $user->getUserIdentifier(),
            $user,
            'Authentification à deux facteurs désactivée',
            $request
        );
    }

    public function logMfaSuccess(User $user, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_MFA_SUCCESS,
            'Validation MFA réussie',
            $user->getUserIdentifier(),
            $user,
            'Code TOTP ou de secours validé avec succès',
            $request
        );
    }

    public function logMfaFailure(User $user, ?Request $request = null): void
    {
        $this->log(
            SecurityAuditLog::EVENT_MFA_FAILURE,
            'Échec validation MFA',
            $user->getUserIdentifier(),
            $user,
            'Code TOTP ou de secours invalide',
            $request
        );
    }

    public function logAccountDisabled(User $targetUser, ?User $actor = null, ?Request $request = null): void
    {
        $actorInfo = $actor ? sprintf(' par %s', $actor->getUserIdentifier()) : '';
        $this->log(
            SecurityAuditLog::EVENT_ACCOUNT_DISABLED,
            'Compte désactivé',
            $targetUser->getUserIdentifier(),
            $targetUser,
            sprintf('Le compte a été désactivé%s', $actorInfo),
            $request
        );
    }

    public function logAccountEnabled(User $targetUser, ?User $actor = null, ?Request $request = null): void
    {
        $actorInfo = $actor ? sprintf(' par %s', $actor->getUserIdentifier()) : '';
        $this->log(
            SecurityAuditLog::EVENT_ACCOUNT_ENABLED,
            'Compte activé',
            $targetUser->getUserIdentifier(),
            $targetUser,
            sprintf('Le compte a été activé%s', $actorInfo),
            $request
        );
    }

    public function logRoleChanged(User $targetUser, array $oldRoles, array $newRoles, ?User $actor = null, ?Request $request = null): void
    {
        $actorInfo = $actor ? sprintf(' par %s', $actor->getUserIdentifier()) : '';
        $this->log(
            SecurityAuditLog::EVENT_ROLE_CHANGED,
            'Modification de rôles',
            $targetUser->getUserIdentifier(),
            $targetUser,
            sprintf('Rôles modifiés de [%s] vers [%s]%s', implode(', ', $oldRoles), implode(', ', $newRoles), $actorInfo),
            $request
        );
    }
}
