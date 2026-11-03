<?php

namespace App\MultiTenancy\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TenantSuspendedException extends AccessDeniedHttpException
{
    public function __construct(string $message = 'L\'accès à cette institution est actuellement suspendu ou archivé.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
