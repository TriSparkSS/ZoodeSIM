<?php

namespace Database\Factories;

use App\Models\PricingSlab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingSlab>
 */
class PricingSlabFactory extends Factory
{
    protected $model = PricingSlab::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'min_amount' => '0.00',
            'max_amount' => '40.00',
            'percentage' => '5.00',
            'is_active' => true,
            'priority' => 10,
        ];
    }
}
