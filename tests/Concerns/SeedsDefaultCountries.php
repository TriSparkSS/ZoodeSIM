<?php

namespace Tests\Concerns;

use Database\Seeders\CountrySeeder;

trait SeedsDefaultCountries
{
    protected function seedDefaultCountries(): void
    {
        $this->seed(CountrySeeder::class);
    }
}
