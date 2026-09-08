<?php

namespace App\Services\Logging\Contracts;

use App\Services\Logging\ApiLogEntry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

interface ApiLoggerServiceInterface
{
    public function log(ApiLogEntry $entry): void;

    /**
     * @param  array<string, mixed>  $requestHeaders
     * @param  array<string, mixed>|null  $requestBody
     * @param  array<string, mixed>|null  $responseHeaders
     */
    public function logHttp(
        string $type,
        string $service,
        string $method,
        string $endpoint,
        string $fullUrl,
        array $requestHeaders,
        mixed $requestBody,
        ?int $responseStatus,
        mixed $responseHeaders,
        mixed $responseBody,
        int $responseTimeMs,
        ?string $errorMessage = null,
        ?string $ipAddress = null,
        ?string $userId = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): void;

    public function logInternalRequest(
        Request $request,
        ?SymfonyResponse $response,
        int $startedHrtime,
        ?Throwable $exception = null,
    ): void;

    public function elapsedMs(int $startedHrtime): int;
}
