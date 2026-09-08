<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class EsimPurchaseException extends RuntimeException
{
    public function __construct(
        public readonly string $translationKey,
        public readonly int $httpStatus = 422,
        string $logMessage = 'eSIM purchase failed',
        ?Throwable $previous = null,
    ) {
        parent::__construct($logMessage, 0, $previous);
    }

    public function userMessage(): string
    {
        return __($this->translationKey);
    }
}
