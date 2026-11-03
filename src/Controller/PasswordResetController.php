<?php

namespace App\Controller;

use App\Form\ResetPasswordRequestType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\PasswordResetService;
use App\Service\SecurityAuditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
        private readonly SecurityAuditService $auditService,
        #[Target('password_reset.limiter')]
        private readonly RateLimiterFactory $passwordResetLimiter
    ) {
    }

    #[Route('/reset-password', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function request(Request $request, UserRepository $userRepository): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Apply rate limiting on password reset requests per IP
            $limiter = $this->passwordResetLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Trop de tentatives de réinitialisation. Veuillez patienter avant de réessayer.');
                return $this->redirectToRoute('app_forgot_password_request');
            }

            $email = (string) $form->get('email')->getData();
            $this->auditService->logPasswordResetRequested($email, $request);

            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user && $user->isVerified()) {
                $rawToken = $this->passwordResetService->createResetToken($user);
                $this->passwordResetService->sendResetEmail($user, $rawToken);
            }

            // Always show the same response regardless of whether the user exists
            return $this->redirectToRoute('app_check_email');
        }

        return $this->render('security/reset-password.html.twig', [
            'requestForm' => $form,
        ]);
    }

    #[Route('/reset-password/check-email', name: 'app_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('security/check_email.html.twig');
    }

    #[Route('/reset-password/reset/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(Request $request, string $token): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $resetToken = $this->passwordResetService->validateToken($token);

        if (!$resetToken) {
            $this->addFlash('error', 'Ce lien de réinitialisation de mot de passe est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = (string) $form->get('plainPassword')->getData();
            $this->passwordResetService->resetPassword($resetToken, $newPassword);

            $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/new-password.html.twig', [
            'resetForm' => $form,
        ]);
    }
}
