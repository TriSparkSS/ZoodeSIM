<?php

namespace App\Services\ResellPortal;

use App\Exceptions\ResellPortalException;
use App\Models\ApiLog;
use App\Services\Logging\Contracts\ApiLoggerServiceInterface;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;
use App\Support\ApiLogContext;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResellPortalClient implements ResellPortalClientInterface
{
    public function __construct(
        protected ApiLoggerServiceInterface $apiLogger,
        protected ApiLogContext $logContext,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        return $this->request('POST', $path, payload: $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEsimPackages(?string $location = null): array
    {
        $query = [];

        if ($location !== null && $location !== '') {
            $query['location'] = strtoupper($location);
        }

        return $this->get('esim-packages', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function createClient(string $name, string $email, ?string $phone = null): array
    {
        $payload = [
            'name' => $name,
            'email' => $email,
        ];

        if ($phone !== null && $phone !== '') {
            $payload['phone'] = $phone;
        }

        return $this->post('clients', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function createEsimOrder(int $clientId, string $packageCode): array
    {
        return $this->post('orders', [
            'client_id' => $clientId,
            'product_key' => 'esim',
            'package_code' => $packageCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function request(string $method, string $path, array $query = [], array $payload = []): array
    {
        $this->assertConfigured();

        $normalizedPath = ltrim($path, '/');
        $verb = strtoupper($method);
        $url = $this->absoluteUrl($normalizedPath, $query);
        $requestBody = $verb === 'GET' ? $query : $payload;
        $started = hrtime(true);

        try {
            $pending = $this->http();

            $response = match ($verb) {
                'GET' => $pending->get($normalizedPath, $query),
                'POST' => $pending->post($normalizedPath, $payload),
                'PUT' => $pending->put($normalizedPath, $payload),
                'PATCH' => $pending->patch($normalizedPath, $payload),
                'DELETE' => $pending->delete($normalizedPath),
                default => throw new ResellPortalException('invalid_response', 503, 'Unsupported ResellPortal HTTP method'),
            };
        } catch (ConnectionException $e) {
            $reason = str_contains(strtolower($e->getMessage()), 'timed out') ? 'timeout' : 'connection';

            $this->recordThirdPartyLog(
                $verb,
                $normalizedPath,
                $url,
                $requestBody,
                null,
                $this->apiLogger->elapsedMs($started),
                $reason.': '.$e->getMessage(),
            );
            $this->logFailure($normalizedPath, $method, $reason, $e->getMessage());

            throw new ResellPortalException($reason, 503, 'ResellPortal connection failed', $e);
        }

        $this->recordThirdPartyLog(
            $verb,
            $normalizedPath,
            $url,
            $requestBody,
            $response,
            $this->apiLogger->elapsedMs($started),
            $this->thirdPartyErrorMessage($response),
        );

        return $this->decode($response, $normalizedPath, $method);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(Response $response, string $path, string $method): array
    {
        $status = $response->status();

        if (in_array($status, [401, 403], true)) {
            $this->logFailure($path, $method, 'authentication', 'HTTP '.$status);

            throw new ResellPortalException('authentication', 503, 'ResellPortal authentication failed');
        }

        if ($status >= 500) {
            $this->logFailure($path, $method, 'server_error', 'HTTP '.$status);

            throw new ResellPortalException('server_error', 503, 'ResellPortal server error');
        }

        if ($status >= 400) {
            $this->logFailure($path, $method, 'client_error', 'HTTP '.$status);

            throw new ResellPortalException('client_error', 503, 'ResellPortal client error');
        }

        $body = trim($response->body());

        if ($body === '') {
            $this->logFailure($path, $method, 'invalid_response', 'Empty response body');

            throw new ResellPortalException('invalid_response', 503, 'Empty ResellPortal response');
        }

        $json = $response->json();

        if (! is_array($json)) {
            $this->logFailure($path, $method, 'invalid_response', 'Non-JSON response');

            throw new ResellPortalException('invalid_response', 503, 'Invalid ResellPortal JSON');
        }

        if (array_key_exists('success', $json) && $json['success'] === false) {
            $this->logFailure($path, $method, 'client_error', 'Provider returned success=false');

            throw new ResellPortalException('client_error', 503, 'ResellPortal returned an error payload');
        }

        return $json;
    }

    protected function http(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.resellportal.base_url'), '/').'/';

        return Http::baseUrl($baseUrl)
            ->timeout((int) config('services.resellportal.timeout', 15))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-API-Key' => (string) config('services.resellportal.api_key'),
                'X-API-Secret' => (string) config('services.resellportal.api_secret'),
                'Content-Type' => 'application/json',
            ]);
    }

    protected function assertConfigured(): void
    {
        $baseUrl = trim((string) config('services.resellportal.base_url'));
        $key = trim((string) config('services.resellportal.api_key'));
        $secret = trim((string) config('services.resellportal.api_secret'));

        if ($baseUrl === '' || $key === '' || $secret === '') {
            $this->recordThirdPartyLog(
                'N/A',
                '(unconfigured)',
                $baseUrl,
                null,
                null,
                0,
                'Missing ResellPortal configuration',
            );
            $this->logFailure('(unconfigured)', 'N/A', 'authentication', 'Missing ResellPortal configuration');

            throw new ResellPortalException('authentication', 503, 'ResellPortal is not configured');
        }
    }

    /**
     * @param  array<string, mixed>|null  $requestBody
     */
    protected function recordThirdPartyLog(
        string $method,
        string $endpoint,
        string $url,
        mixed $requestBody,
        ?Response $response,
        int $responseTimeMs,
        ?string $errorMessage,
    ): void {
        try {
            $this->apiLogger->logHttp(
                type: ApiLog::TYPE_THIRD_PARTY,
                service: ApiLog::SERVICE_RESELLPORTAL,
                method: $method,
                endpoint: '/'.$endpoint,
                fullUrl: $url,
                requestHeaders: [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-API-Key' => (string) config('services.resellportal.api_key'),
                    'X-API-Secret' => (string) config('services.resellportal.api_secret'),
                ],
                requestBody: $requestBody,
                responseStatus: $response?->status(),
                responseHeaders: $response?->headers() ?? [],
                responseBody: $this->thirdPartyResponseBody($response),
                responseTimeMs: $responseTimeMs,
                errorMessage: $errorMessage,
                ipAddress: request()?->ip(),
                userId: $this->logContext->userId,
                referenceType: $this->logContext->referenceType,
                referenceId: $this->logContext->referenceId,
            );
        } catch (Throwable) {
            // Logging must never break the provider call.
        }
    }

    protected function thirdPartyErrorMessage(Response $response): ?string
    {
        if (! $response->successful()) {
            return 'HTTP '.$response->status();
        }

        $json = $response->json();

        if (is_array($json) && array_key_exists('success', $json) && $json['success'] === false) {
            return 'Provider returned success=false';
        }

        return null;
    }

    protected function thirdPartyResponseBody(?Response $response): mixed
    {
        if ($response === null) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : ['_raw' => $response->body()];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function absoluteUrl(string $path, array $query = []): string
    {
        $base = rtrim((string) config('services.resellportal.base_url'), '/');
        $url = $base.'/'.$path;

        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query);
    }

    protected function logFailure(string $path, string $method, string $reason, string $detail): void
    {
        Log::warning('ResellPortal request failed', [
            'path' => $path,
            'method' => $method,
            'reason' => $reason,
            'detail' => $detail,
        ]);
    }
}
