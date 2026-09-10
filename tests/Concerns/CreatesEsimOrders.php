<?php

namespace Tests\Concerns;

use App\Models\EsimOrder;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesEsimOrders
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeEsimOrder(User $user, array $overrides = []): EsimOrder
    {
        return EsimOrder::query()->create(array_merge([
            'user_id' => $user->id,
            'idempotency_key' => (string) Str::uuid(),
            'resellportal_client_id' => '123',
            'package_code' => 'PHAJHEAYP',
            'package_name' => 'United States 1GB 7Days',
            'package_location' => 'US',
            'package_data_volume' => '1GB',
            'package_duration' => 7,
            'provider_cost' => '1.80',
            'markup_percentage' => '5.00',
            'markup_amount' => '0.09',
            'customer_price' => '1.89',
            'discount_percentage' => '0.00',
            'discount_amount' => '0.00',
            'charged_amount' => '1.89',
            'currency' => 'USD',
            'payment_status' => EsimOrder::PAYMENT_PAID,
            'order_status' => EsimOrder::STATUS_ACTIVE,
        ], $overrides));
    }
}
