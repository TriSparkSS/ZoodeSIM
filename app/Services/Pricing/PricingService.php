<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\EsimPackageData;
use App\DataTransferObjects\EsimPriceQuote;
use App\Exceptions\PricingUnavailableException;
use App\Models\PricingSlab;
use App\Services\Esim\Contracts\EsimPricingServiceInterface;
use App\Services\Pricing\Contracts\PricingServiceInterface;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PricingService implements EsimPricingServiceInterface, PricingServiceInterface
{
    public function quote(EsimPackageData $package): EsimPriceQuote
    {
        return $this->quoteFromProviderCost($package->price);
    }

    public function quoteFromProviderCost(string|int|float $providerCost, ?string $currency = null): EsimPriceQuote
    {
        $currency = strtoupper($currency ?? (string) config('pricing.currency', 'USD'));
        $cost = Money::fromDecimal($providerCost, $currency);
        $slab = $this->findMatchingSlab($cost);

        if ($slab === null) {
            Log::warning('Pricing unavailable: no matching slab', [
                'provider_cost_cents' => $cost->cents,
                'currency' => $currency,
            ]);

            throw new PricingUnavailableException;
        }

        $markup = $cost->percentageOf((string) $slab->percentage);
        $customer = $cost->add($markup);
        $percentage = Money::normalizeDecimal((string) $slab->percentage);

        $quote = new EsimPriceQuote(
            providerCost: $cost,
            markupAmount: $markup,
            customerPrice: $customer,
            markupPercentage: $percentage,
            slabId: $slab->id,
        );

        Log::info('eSIM price calculated', [
            'slab_id' => $slab->id,
            'provider_cost_cents' => $cost->cents,
            'markup_percentage' => $percentage,
            'markup_amount_cents' => $markup->cents,
            'customer_price_cents' => $customer->cents,
            'currency' => $currency,
        ]);

        return $quote;
    }

    public function findMatchingSlab(Money $cost): ?PricingSlab
    {
        $matches = $this->activeSlabs()
            ->filter(fn (PricingSlab $slab): bool => $slab->matchesCostCents($cost->cents))
            ->sortBy([
                ['priority', 'asc'],
                ['min_amount', 'asc'],
            ])
            ->values();

        return $matches->first();
    }

    /**
     * @return Collection<int, PricingSlab>
     */
    public function activeSlabs(): Collection
    {
        $key = (string) config('pricing.cache_key', 'pricing_slabs_active');
        $ttl = (int) config('pricing.cache_ttl', 3600);
        $cached = Cache::get($key);
        $rows = $this->slabAttributeRows($cached);

        if ($rows === null) {
            if ($cached !== null) {
                Cache::forget($key);
            }

            $rows = Cache::remember($key, $ttl, fn () => $this->loadActiveSlabAttributes());
            $rows = $this->slabAttributeRows($rows) ?? $this->loadActiveSlabAttributes();
        }

        return PricingSlab::hydrate($rows);
    }

    public static function forgetCache(): void
    {
        Cache::forget((string) config('pricing.cache_key', 'pricing_slabs_active'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function loadActiveSlabAttributes(): array
    {
        return PricingSlab::query()
            ->active()
            ->orderBy('priority')
            ->orderBy('min_amount')
            ->get()
            ->map(fn (PricingSlab $slab) => $slab->getAttributes())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    protected function slabAttributeRows(mixed $cached): ?array
    {
        if (is_array($cached)) {
            return array_is_list($cached) ? $cached : array_values($cached);
        }

        if ($cached instanceof Collection) {
            if ($cached->contains(fn (mixed $slab): bool => ! $slab instanceof PricingSlab)) {
                return null;
            }

            return $cached
                ->map(fn (PricingSlab $slab) => $slab->getAttributes())
                ->values()
                ->all();
        }

        return null;
    }
}
