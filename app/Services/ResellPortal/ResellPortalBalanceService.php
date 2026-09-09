<?php

namespace App\Services\ResellPortal;

use App\DataTransferObjects\ResellPortalBalance;
use App\Exceptions\ResellPortalException;
use App\Services\ResellPortal\Contracts\ResellPortalBalanceServiceInterface;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ResellPortalBalanceService implements ResellPortalBalanceServiceInterface
{
    public const CACHE_KEY = 'resellportal.balance';

    public function __construct(
        protected ResellPortalClientInterface $client,
    ) {}

    public function current(): ResellPortalBalance
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $this->toDto($cached, fromCache: true);
        }

        return $this->toDto($this->fetchAndStore(), fromCache: false);
    }

    public function refresh(): ResellPortalBalance
    {
        Cache::forget(self::CACHE_KEY);

        return $this->current();
    }

    /**
     * @return array{available: bool, amount: string|null, currency: string|null, fetched_at: string}
     */
    protected function fetchAndStore(): array
    {
        try {
            $payload = $this->mapResponse($this->client->getBalance());
        } catch (ResellPortalException) {
            $payload = $this->unavailablePayload();
        }

        $ttl = ($payload['available'] ?? false) ? $this->ttl() : $this->failureTtl();
        Cache::put(self::CACHE_KEY, [
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'available' => $payload['available'],
            'fetched_at' => $payload['fetched_at'],
        ], $ttl);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{available: bool, amount: string|null, currency: string|null, fetched_at: string}
     */
    protected function mapResponse(array $response): array
    {
        $amount = $response['balance'] ?? null;
        $currency = isset($response['currency']) ? trim((string) $response['currency']) : '';

        if ($amount === null || $amount === '' || $currency === '') {
            return $this->unavailablePayload();
        }

        return [
            'available' => true,
            'amount' => number_format((float) $amount, 2, '.', ''),
            'currency' => $currency,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{available: bool, amount: null, currency: null, fetched_at: string}
     */
    protected function unavailablePayload(): array
    {
        return [
            'available' => false,
            'amount' => null,
            'currency' => null,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function toDto(array $payload, bool $fromCache): ResellPortalBalance
    {
        $amount = $payload['amount'] ?? null;
        $currency = isset($payload['currency']) ? trim((string) $payload['currency']) : '';
        $available = (bool) ($payload['available'] ?? false) && $amount !== null && $amount !== '' && $currency !== '';

        return new ResellPortalBalance(
            available: $available,
            amount: $available ? (string) $amount : null,
            currency: $available ? $currency : null,
            fetchedAt: isset($payload['fetched_at'])
                ? Carbon::parse((string) $payload['fetched_at'])
                : now(),
            fromCache: $fromCache,
        );
    }

    protected function ttl(): int
    {
        return max(1, (int) config('services.resellportal.balance_cache_ttl', 60));
    }

    protected function failureTtl(): int
    {
        return max(1, min(30, $this->ttl()));
    }
}
