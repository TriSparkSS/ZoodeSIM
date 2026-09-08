<?php

namespace App\Services\Esim\Contracts;

use App\DataTransferObjects\EsimPackageData;
use App\DataTransferObjects\EsimPriceQuote;

interface EsimPricingServiceInterface
{
    public function quote(EsimPackageData $package): EsimPriceQuote;
}
