<?php

namespace App\MultiTenancy\EventListener;

use App\MultiTenancy\TenantContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TenantRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 5 runs after Symfony Firewall (priority 8) so the user is authenticated
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Apply tenant filter based on resolved tenant
        $this->tenantContext->applyDoctrineFilter();
    }
}
