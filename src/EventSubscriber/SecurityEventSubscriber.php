<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\SecurityAuditService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SecurityAuditService $auditService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if ($token && $token->getUser() instanceof User) {
            $this->auditService->logLogout($token->getUser(), $event->getRequest());
        }
    }
}
