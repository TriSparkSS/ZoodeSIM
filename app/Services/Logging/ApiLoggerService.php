<?php

namespace App\Services\Logging;

use App\Models\ApiLog;
use App\Services\Logging\Contracts\ApiLoggerServiceInterface;
use App\Support\ApiLogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ApiLoggerService implements ApiLoggerServiceInterface
{
    public function __construct(
        protected SensitiveDataRedactor $redactor,
        protected ApiLogContext $context,
    ) {}

    public function log(ApiLogEntry $entry): void
    {
        if (! config('api_logging.enabled', true)) {
            return;
        }

        try {
            ApiLog::query()->create([
                'type' => $entry->type,
                'service' => $entry->service,
                'method' => strtoupper($entry->method),
                'endpoint' => mb_substr($entry->endpoint, 0, 512),
                'full_url' => $this->redactor->redactString($entry->fullUrl),
                'request_headers' => $this->sanitizePayload($this->redactor->redactHeaders($this->asArray($entry->requestHeaders))),
                'request_body' => $this->sanitizePayload($this->redactor->redact($entry->requestBody)),
                'response_status' => $entry->responseStatus,
                'response_headers' => $this->sanitizePayload($this->redactor->redactHeaders($this->asArray($entry->responseHeaders))),
                'response_body' => $this->sanitizePayload($this->redactor->redact($entry->responseBody)),
                'response_time_ms' => max(0, $entry->responseTimeMs),
                'ip_address' => $entry->ipAddress,
                'user_id' => $entry->userId ?? $this->context->userId,
                'reference_type' => $entry->referenceType ?? $this->context->referenceType,
                'reference_id' => $entry->referenceId ?? $this->context->referenceId,
                'error_message' => $entry->errorMessage !== null
                    ? $this->redactor->redactString($entry->errorMessage)
                    : null,
            ]);
        } catch (Throwable $e) {
            Log::warning('API log persistence failed', [
                'exception' => $e::class,
            ]);
        }
    }

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
    ): void {
        $this->log(new ApiLogEntry(
            type: $type,
            service: $service,
            method: $method,
            endpoint: $endpoint,
            fullUrl: $fullUrl,
            requestHeaders: $requestHeaders,
            requestBody: $requestBody,
            responseStatus: $responseStatus,
            responseHeaders: $responseHeaders,
            responseBody: $responseBody,
            responseTimeMs: $responseTimeMs,
            ipAddress: $ipAddress,
            userId: $userId ?? $this->resolveUserId(),
            referenceType: $referenceType,
            referenceId: $referenceId,
            errorMessage: $errorMessage,
        ));
    }

    public function logInternalRequest(
        Request $request,
        ?SymfonyResponse $response,
        int $startedHrtime,
        ?Throwable $exception = null,
    ): void {
        $this->logHttp(
            type: ApiLog::TYPE_INTERNAL,
            service: ApiLog::SERVICE_PORTAL,
            method: $request->method(),
            endpoint: '/'.ltrim($request->path(), '/'),
            fullUrl: $request->fullUrl(),
            requestHeaders: $request->headers->all(),
            requestBody: $this->requestPayload($request),
            responseStatus: $response?->getStatusCode(),
            responseHeaders: $response?->headers->all(),
            responseBody: $response !== null ? $this->decodeResponseBody($response) : null,
            responseTimeMs: $this->elapsedMs($startedHrtime),
            errorMessage: $exception?->getMessage(),
            ipAddress: $request->ip(),
            userId: $this->resolveUserId($request),
        );
    }

    public function elapsedMs(int $startedHrtime): int
    {
        return (int) max(0, (int) round((hrtime(true) - $startedHrtime) / 1_000_000));
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestPayload(Request $request): array
    {
        $payload = $request->except($this->fileKeys($request));

        foreach ($request->allFiles() as $key => $file) {
            $payload[$key] = is_array($file)
                ? '[files omitted]'
                : '[file omitted]';
        }

        return $payload;
    }

    /**
     * @return list<string>
     */
    protected function fileKeys(Request $request): array
    {
        return array_keys($request->allFiles());
    }

    protected function decodeResponseBody(SymfonyResponse $response): mixed
    {
        $contentType = (string) $response->headers->get('Content-Type', '');
        $body = $response->getContent();

        if (! is_string($body) || $body === '') {
            return null;
        }

        if ($this->isBinary($body, $contentType)) {
            return ['_omitted' => 'binary_or_file_response'];
        }

        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return ['_raw' => $body];
    }

    protected function isBinary(string $body, string $contentType): bool
    {
        $type = strtolower($contentType);

        if (
            str_contains($type, 'octet-stream')
            || str_contains($type, 'multipart/')
            || str_starts_with($type, 'image/')
            || str_starts_with($type, 'video/')
            || str_starts_with($type, 'audio/')
            || str_starts_with($type, 'application/pdf')
        ) {
            return true;
        }

        return str_contains($body, "\0");
    }

    protected function sanitizePayload(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($encoded)) {
            return ['_omitted' => 'unencodable_payload'];
        }

        $limit = (int) config('api_logging.max_body_bytes', 65536);

        if (strlen($encoded) <= $limit) {
            return $value;
        }

        return [
            '_truncated' => true,
            '_bytes' => strlen($encoded),
            '_preview' => mb_substr($encoded, 0, min(2000, $limit)).'…',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    protected function resolveUserId(?Request $request = null): ?string
    {
        if ($this->context->userId) {
            return $this->context->userId;
        }

        $request ??= request();
        $user = $request?->user();

        if ($user === null) {
            return null;
        }

        $id = $user->getAuthIdentifier();

        return is_string($id) ? $id : null;
    }
}
