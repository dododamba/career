<?php

namespace App\EventSubscriber;

use App\Service\OrganizationContextService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InstallationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrganizationContextService $organizationContextService,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 30],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Ignorer les assets, le profiler et les ressources statiques
        if (preg_match('#^/(_(profiler|wdt)|assets|css|images|js|build|uploads)#', $path)) {
            return;
        }

        $isInstalled = $this->organizationContextService->isInstalled();

        // 1. Si l'application n'est pas installée, rediriger vers /install
        if (!$isInstalled) {
            if (!str_starts_with($path, '/install')) {
                $installUrl = $this->urlGenerator->generate('app_install_index');
                $event->setResponse(new RedirectResponse($installUrl));
            }
            return;
        }

        // 2. Si l'application est déjà installée, interdire l'accès à /install
        if (str_starts_with($path, '/install')) {
            $redirectUrl = $this->urlGenerator->generate('app_login');
            $event->setResponse(new RedirectResponse($redirectUrl));
        }
    }
}
