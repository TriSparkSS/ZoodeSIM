<?php

namespace App\Exceptions;

class PricingUnavailableException extends EsimPurchaseException
{
    public function __construct(string $logMessage = 'No matching pricing slab')
    {
        parent::__construct('api.esim.pricing_unavailable', 422, $logMessage);
    }
}
