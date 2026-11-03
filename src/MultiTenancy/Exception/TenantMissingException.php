<?php

namespace App\MultiTenancy\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TenantMissingException extends AccessDeniedHttpException
{
    public function __construct(string $message = 'Aucun contexte d\'institution (tenant) n\'est disponible pour cette opération.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
