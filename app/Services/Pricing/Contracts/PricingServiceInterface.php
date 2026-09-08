<?php

namespace App\Services\Pricing\Contracts;

use App\DataTransferObjects\EsimPackageData;
use App\DataTransferObjects\EsimPriceQuote;

interface PricingServiceInterface
{
    public function quoteFromProviderCost(string|int|float $providerCost, ?string $currency = null): EsimPriceQuote;

    public function quote(EsimPackageData $package): EsimPriceQuote;
}
