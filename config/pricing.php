<?php

return [
    /*
    | The portal currently charges in a single configured currency.
    | PricingService does not convert between currencies.
    */
    'currency' => env('PRICING_CURRENCY', 'USD'),

    'cache_key' => 'pricing_slabs_active',

    'cache_ttl' => (int) env('PRICING_CACHE_TTL', 3600),
];
