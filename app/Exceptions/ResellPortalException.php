<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ResellPortalException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly int $httpStatus = 503,
        string $logMessage = 'ResellPortal request failed',
        ?Throwable $previous = null,
    ) {
        parent::__construct($logMessage, 0, $previous);
    }

    public function userMessage(): string
    {
        return __('api.esim.unavailable');
    }
}
