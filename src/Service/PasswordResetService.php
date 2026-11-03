<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetService
{
    private const TOKEN_LIFETIME = '+1 hour';

    public function __construct(
        private readonly PasswordResetTokenRepository $tokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SecurityAuditService $auditService,
        private readonly ?LoggerInterface $logger = null,
        #[Autowire('%app.name%')]
        private readonly string $appName = 'Suivi des Carrières',
        #[Autowire('%app.mailer_from%')]
        private readonly string $mailerFrom = 'email@career-host.com'
    ) {
    }

    public function createResetToken(User $user): string
    {
        // Invalidate any existing unused tokens for this user
        $this->tokenRepository->invalidatePreviousTokens($user);

        // Generate cryptographically secure random token (64 hex characters)
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = new \DateTimeImmutable(self::TOKEN_LIFETIME);

        $resetToken = new PasswordResetToken($user, $tokenHash, $expiresAt);
        $this->entityManager->persist($resetToken);
        $this->entityManager->flush();

        return $rawToken;
    }

    public function sendResetEmail(User $user, string $rawToken): void
    {
        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $rawToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from(new Address($this->mailerFrom, $this->appName))
            ->to((string) $user->getEmail())
            ->subject($this->appName . ' — Réinitialisation de votre mot de passe')
            ->html(sprintf(
                '<h2>Réinitialisation de votre mot de passe</h2>' .
                '<p>Bonjour %s,</p>' .
                '<p>Une demande de réinitialisation de mot de passe a été effectuée pour votre compte %s.</p>' .
                '<p><a href="%s" style="display:inline-block;padding:10px 20px;background-color:#002664;color:#fff;text-decoration:none;border-radius:4px;">Réinitialiser mon mot de passe</a></p>' .
                '<p>Ce lien est valable pendant 1 heure et ne peut être utilisé qu\'une seule fois.</p>' .
                '<p>Si vous n\'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>' .
                '<p style="color:#888;font-size:12px;">URL directe: %s</p>',
                htmlspecialchars($user->getFullName() ?: $user->getUserIdentifier()),
                htmlspecialchars($this->appName),
                htmlspecialchars($resetUrl),
                htmlspecialchars($resetUrl)
            ));

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger?->error('Erreur lors de l\'envoi de l\'email de réinitialisation : ' . $e->getMessage());
        }
    }

    public function validateToken(string $rawToken): ?PasswordResetToken
    {
        $tokenHash = hash('sha256', $rawToken);
        return $this->tokenRepository->findValidToken($tokenHash);
    }

    public function resetPassword(PasswordResetToken $token, string $plainPassword): void
    {
        $user = $token->getUser();
        if (!$user) {
            throw new \LogicException('Aucun utilisateur associé à ce jeton de réinitialisation.');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->setPasswordChangedAt(new \DateTimeImmutable());

        $token->setUsedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
        $this->auditService->logPasswordResetCompleted($user);
    }
}
