<?php

namespace Database\Seeders;

use App\Models\PricingSlab;
use Illuminate\Database\Seeder;

class PricingSlabSeeder extends Seeder
{
    /**
     * Default slabs use half-open ranges: min_amount <= cost < max_amount.
     *
     * 0–40 is included so typical ResellPortal prices (~$1.80) remain purchasable.
     * 40–50, 50–100, and 100–200 match the STEP 3 markup table.
     * 200.00 is outside the last slab and is intentionally unpriced.
     *
     * @var list<array{min_amount: string, max_amount: string, percentage: string, priority: int}>
     */
    public const DEFAULTS = [
        ['min_amount' => '0.00', 'max_amount' => '40.00', 'percentage' => '5.00', 'priority' => 10],
        ['min_amount' => '40.00', 'max_amount' => '50.00', 'percentage' => '5.00', 'priority' => 20],
        ['min_amount' => '50.00', 'max_amount' => '100.00', 'percentage' => '4.00', 'priority' => 30],
        ['min_amount' => '100.00', 'max_amount' => '200.00', 'percentage' => '3.00', 'priority' => 40],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $slab) {
            $exists = PricingSlab::query()
                ->where('min_amount', $slab['min_amount'])
                ->where('max_amount', $slab['max_amount'])
                ->where('percentage', $slab['percentage'])
                ->exists();

            if ($exists) {
                continue;
            }

            PricingSlab::query()->create([
                ...$slab,
                'is_active' => true,
            ]);
        }
    }
}
