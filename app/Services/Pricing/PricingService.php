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
        return Cache::remember(
            (string) config('pricing.cache_key', 'pricing_slabs_active'),
            (int) config('pricing.cache_ttl', 3600),
            fn () => PricingSlab::query()
                ->active()
                ->orderBy('priority')
                ->orderBy('min_amount')
                ->get(),
        );
    }

    public static function forgetCache(): void
    {
        Cache::forget((string) config('pricing.cache_key', 'pricing_slabs_active'));
    }
}
