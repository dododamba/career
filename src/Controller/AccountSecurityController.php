<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Repository\SecurityAuditLogRepository;
use App\Service\SecurityAuditService;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account')]
#[IsGranted('ROLE_USER')]
class AccountSecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SecurityAuditService $auditService,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/security', name: 'app_account_security', methods: ['GET'])]
    public function index(SecurityAuditLogRepository $auditLogRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $recentEvents = $auditLogRepository->findByUserIdentifier($user->getUserIdentifier(), 10);

        return $this->render('account/security.html.twig', [
            'user' => $user,
            'recentEvents' => $recentEvents,
        ]);
    }

    #[Route('/change-password', name: 'app_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = (string) $form->get('newPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);

            $user->setPassword($hashedPassword);
            $user->setPasswordChangedAt(new \DateTimeImmutable());

            $this->entityManager->flush();
            $this->auditService->logPasswordChanged($user, $request);

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_account_security');
        }

        return $this->render('account/change_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/2fa/setup', name: 'app_2fa_setup', methods: ['GET'])]
    public function setup2fa(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTotpAuthenticationEnabled()) {
            $this->addFlash('info', 'L\'authentification à deux facteurs est déjà activée sur votre compte.');
            return $this->redirectToRoute('app_account_security');
        }

        $totp = TOTP::generate();
        $totp->setLabel($user->getEmail());
        $totp->setIssuer('PRIMATECH');

        $secret = $totp->getSecret();
        $request->getSession()->set('2fa_setup_secret', $secret);

        return $this->render('account/2fa_setup.html.twig', [
            'secret' => $secret,
            'provisioningUri' => $totp->getProvisioningUri(),
        ]);
    }

    #[Route('/2fa/enable', name: 'app_2fa_enable', methods: ['POST'])]
    public function enable2fa(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $session = $request->getSession();
        $secret = $session->get('2fa_setup_secret');

        if (!$secret) {
            $this->addFlash('error', 'Session de configuration expirée. Veuillez recommencer.');
            return $this->redirectToRoute('app_2fa_setup');
        }

        $code = trim((string) $request->request->get('code', ''));

        $totp = TOTP::createFromSecret($secret);
        if (!$totp->verify($code)) {
            $this->addFlash('error', 'Code de vérification invalide. Veuillez vérifier l\'heure de votre appareil.');
            return $this->redirectToRoute('app_2fa_setup');
        }

        // Generate 8 random backup recovery codes
        $rawBackupCodes = [];
        $hashedBackupCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $rawCode = strtoupper(bin2hex(random_bytes(4)));
            $rawBackupCodes[] = $rawCode;
            $hashedBackupCodes[] = hash('sha256', $rawCode);
        }

        $user->setTotpSecret($secret);
        $user->setIsTotpEnabled(true);
        $user->setBackupCodes($hashedBackupCodes);

        $this->entityManager->flush();
        $session->remove('2fa_setup_secret');

        $this->auditService->logMfaEnabled($user, $request);

        return $this->render('account/2fa_backup_codes.html.twig', [
            'backupCodes' => $rawBackupCodes,
        ]);
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable', methods: ['POST'])]
    public function disable2fa(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('disable_2fa', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_account_security');
        }

        $password = (string) $request->request->get('current_password', '');
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Mot de passe incorrect. Impossible de désactiver la 2FA.');
            return $this->redirectToRoute('app_account_security');
        }

        $user->setIsTotpEnabled(false);
        $user->setTotpSecret(null);
        $user->setBackupCodes([]);

        $this->entityManager->flush();
        $this->auditService->logMfaDisabled($user, $request);

        $this->addFlash('success', 'L\'authentification à deux facteurs a été désactivée.');
        return $this->redirectToRoute('app_account_security');
    }
}
