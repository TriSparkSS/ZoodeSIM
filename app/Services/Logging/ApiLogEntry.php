<?php

namespace App\Services\Logging;

readonly class ApiLogEntry
{
    /**
     * @param  array<string, mixed>|null  $requestHeaders
     * @param  array<string, mixed>|null  $responseHeaders
     */
    public function __construct(
        public string $type,
        public string $service,
        public string $method,
        public string $endpoint,
        public string $fullUrl,
        public mixed $requestHeaders = null,
        public mixed $requestBody = null,
        public ?int $responseStatus = null,
        public mixed $responseHeaders = null,
        public mixed $responseBody = null,
        public int $responseTimeMs = 0,
        public ?string $ipAddress = null,
        public ?string $userId = null,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public ?string $errorMessage = null,
    ) {}
}
