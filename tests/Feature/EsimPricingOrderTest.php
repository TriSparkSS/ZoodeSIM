<?php

namespace Tests\Feature;

use App\DataTransferObjects\EsimPaymentResult;
use App\Models\EsimOrder;
use App\Models\PricingSlab;
use App\Services\Esim\Contracts\EsimPaymentGatewayInterface;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\FundsUserWallet;
use Tests\Concerns\SeedsDefaultPricingSlabs;
use Tests\TestCase;

class EsimPricingOrderTest extends TestCase
{
    use FundsUserWallet;
    use RefreshDatabase;
    use SeedsDefaultPricingSlabs;

    /**
     * @return array<string, mixed>
     */
    protected function providerPackage(float $price, string $code = 'PHAJHEAYP'): array
    {
        return [
            'success' => true,
            'packages' => [[
                'package_code' => $code,
                'name' => 'United States 1GB 7Days',
                'price' => $price,
                'location' => 'US',
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderPayload(): array
    {
        return [
            'success' => true,
            'service_id' => 789,
            'message' => 'E-SIM activated successfully',
            'package' => [
                'name' => 'United States 1GB 7Days',
                'location' => 'US',
            ],
            'amount_charged' => 50.00,
            'esim_details' => [
                'qr_code_url' => 'https://example.test/qr.png',
                'activation_url' => 'https://example.test/activate',
                'iccid' => '8901234567890123456',
                'esim_status' => 'active',
            ],
        ];
    }

    protected function fakeProvider(float $price, string $code = 'PHAJHEAYP'): void
    {
        Http::fake([
            '*/esim-packages*' => Http::response($this->providerPackage($price, $code), 200),
            '*/clients' => Http::response(['success' => true, 'client_id' => 123], 200),
            '*/orders' => Http::response($this->orderPayload(), 200),
        ]);
    }

    public function test_order_stores_immutable_pricing_snapshot(): void
    {
        $this->seedDefaultPricingSlabs();
        $this->fakeProvider(50.00);
        Sanctum::actingAs($this->fundedUser());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated()
            ->assertJsonPath('data.order.price', 49.4);

        $this->assertDatabaseHas('esim_orders', [
            'package_code' => 'PHAJHEAYP',
            'provider_cost' => 50.00,
            'markup_percentage' => 4.00,
            'markup_amount' => 2.00,
            'customer_price' => 52.00,
            'charged_amount' => 49.40,
            'discount_amount' => 2.60,
            'currency' => 'USD',
        ]);
    }

    public function test_changing_slab_does_not_change_old_order(): void
    {
        $this->seedDefaultPricingSlabs();
        $this->fakeProvider(50.00);
        Sanctum::actingAs($this->fundedUser());

        $orderId = $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated()
            ->json('data.order.id');

        $slab = PricingSlab::query()
            ->where('min_amount', '50.00')
            ->where('max_amount', '100.00')
            ->firstOrFail();
        $slab->update(['percentage' => '8.00']);
        PricingService::forgetCache();

        $order = EsimOrder::query()->findOrFail($orderId);
        $this->assertEquals('50.00', $order->provider_cost);
        $this->assertEquals('4.00', $order->markup_percentage);
        $this->assertEquals('2.00', $order->markup_amount);
        $this->assertEquals('52.00', $order->customer_price);
    }

    public function test_new_orders_use_updated_slab(): void
    {
        $this->seedDefaultPricingSlabs();
        $this->fakeProvider(50.00);
        Sanctum::actingAs($this->fundedUser());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $slab = PricingSlab::query()
            ->where('min_amount', '50.00')
            ->where('max_amount', '100.00')
            ->firstOrFail();
        $slab->update(['percentage' => '8.00']);
        PricingService::forgetCache();

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated()
            ->assertJsonPath('data.order.price', 54);

        $this->assertEquals(2, EsimOrder::query()->count());
        $this->assertEqualsCanonicalizing(
            ['52.00', '54.00'],
            EsimOrder::query()->pluck('customer_price')->all(),
        );
    }

    public function test_payment_uses_backend_calculated_customer_price(): void
    {
        $this->seedDefaultPricingSlabs();
        $this->fakeProvider(50.00);

        $this->mock(EsimPaymentGatewayInterface::class, function ($mock) {
            $mock->shouldReceive('settle')
                ->once()
                ->with(\Mockery::on(function (EsimOrder $order) {
                    return bccomp((string) $order->customer_price, '52.00', 2) === 0
                        && bccomp((string) $order->charged_amount, '49.40', 2) === 0
                        && bccomp((string) $order->provider_cost, '50.00', 2) === 0
                        && bccomp((string) $order->markup_percentage, '4.00', 2) === 0;
                }))
                ->andReturn(EsimPaymentResult::paid());
        });

        Sanctum::actingAs($this->fundedUser());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated()
            ->assertJsonPath('data.order.price', 49.4);
    }

    public function test_frontend_cannot_manipulate_provider_or_customer_price(): void
    {
        $this->seedDefaultPricingSlabs();
        $this->fakeProvider(50.00);
        Sanctum::actingAs($this->fundedUser());

        $this->postJson('/api/user/esim/orders', [
            'package_code' => 'PHAJHEAYP',
            'price' => 0.01,
            'markup' => 0,
            'provider_cost' => 0.01,
            'customer_price' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('data.order.price', 49.4);

        $order = EsimOrder::query()->first();
        $this->assertEquals('50.00', $order->provider_cost);
        $this->assertEquals('4.00', $order->markup_percentage);
        $this->assertEquals('2.00', $order->markup_amount);
        $this->assertEquals('52.00', $order->customer_price);
        $this->assertEquals('49.40', $order->charged_amount);
    }

    public function test_purchase_fails_when_no_slab_matches(): void
    {
        $this->seedDefaultPricingSlabs();
        $this->fakeProvider(200.00);
        Sanctum::actingAs($this->fundedUser());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.esim.pricing_unavailable'));

        $this->assertDatabaseCount('esim_orders', 0);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/orders'));
    }
}
