<?php

namespace App\Services\Logging;

class SensitiveDataRedactor
{
    /**
     * @var list<string>
     */
    protected const SENSITIVE_KEYS = [
        'authorization',
        'bearer',
        'x_api_key',
        'x_api_secret',
        'api_key',
        'apikey',
        'api_secret',
        'token',
        'access_token',
        'refresh_token',
        'password',
        'password_confirmation',
        'passwd',
        'secret',
        'client_secret',
        'card_number',
        'cardnumber',
        'card',
        'cvv',
        'cvc',
        'webhook_secret',
        'webhook_secrets',
        'payment_token',
        'credit_card',
        'pan',
        'pin',
        'remember_token',
        'cookie',
        'set_cookie',
        'x_csrf_token',
        'x_xsrf_token',
    ];

    public function __construct(
        protected string $mask = '********',
    ) {
        $this->mask = (string) config('api_logging.mask', $mask);
    }

    public function redactHeaders(array $headers): array
    {
        $redacted = [];

        foreach ($headers as $name => $value) {
            $redacted[$name] = $this->isSensitiveKey((string) $name)
                ? $this->maskHeaderValue((string) $name, $value)
                : $this->redactValue($value);
        }

        return $redacted;
    }

    public function redact(mixed $value): mixed
    {
        return $this->redactValue($value);
    }

    public function redactString(string $content): string
    {
        foreach ($this->knownSecrets() as $secret) {
            $content = str_replace($secret, $this->mask, $content);
        }

        return $content;
    }

    protected function redactValue(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSensitiveKey($key)) {
            return $this->maskHeaderValue($key, $value);
        }

        if (is_array($value)) {
            $redacted = [];

            foreach ($value as $nestedKey => $nestedValue) {
                $redacted[$nestedKey] = $this->redactValue(
                    $nestedValue,
                    is_string($nestedKey) ? $nestedKey : null,
                );
            }

            return $redacted;
        }

        if (is_string($value)) {
            return $this->redactString($value);
        }

        return $value;
    }

    protected function maskHeaderValue(string $key, mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(', ', array_map(fn (mixed $item) => is_scalar($item) ? (string) $item : '', $value));
        }

        $value = is_scalar($value) ? (string) $value : $this->mask;

        if ($this->normalizeKey($key) === 'authorization' && preg_match('/^Bearer\s+/i', $value) === 1) {
            return 'Bearer '.$this->mask;
        }

        return $this->mask;
    }

    public function isSensitiveKey(string $key): bool
    {
        $normalized = $this->normalizeKey($key);

        if (in_array($normalized, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        return str_ends_with($normalized, '_secret');
    }

    protected function normalizeKey(string $key): string
    {
        return strtolower(str_replace(['-', ' '], '_', $key));
    }

    /**
     * @return list<string>
     */
    protected function knownSecrets(): array
    {
        return array_values(array_filter([
            trim((string) config('services.resellportal.api_key')),
            trim((string) config('services.resellportal.api_secret')),
        ], fn (string $secret): bool => strlen($secret) >= 4));
    }
}
