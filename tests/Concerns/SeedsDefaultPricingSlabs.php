<?php

namespace Tests\Concerns;

use Database\Seeders\PricingSlabSeeder;

trait SeedsDefaultPricingSlabs
{
    protected function seedDefaultPricingSlabs(): void
    {
        $this->seed(PricingSlabSeeder::class);
    }
}
