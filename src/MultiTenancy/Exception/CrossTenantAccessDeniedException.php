<?php

namespace App\MultiTenancy\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CrossTenantAccessDeniedException extends AccessDeniedHttpException
{
    public function __construct(string $message = 'Accès interdit : cette ressource appartient à une autre institution.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
