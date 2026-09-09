<?php

namespace Tests\Feature;

use App\DataTransferObjects\EsimPaymentResult;
use App\Models\EsimOrder;
use App\Models\User;
use App\Services\Esim\Contracts\EsimPaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsDefaultPricingSlabs;
use Tests\TestCase;

class EsimOrderApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDefaultPricingSlabs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDefaultPricingSlabs();
    }

    /**
     * @return array<string, mixed>
     */
    protected function packagePayload(): array
    {
        return [
            'success' => true,
            'packages' => [[
                'package_code' => 'PHAJHEAYP',
                'name' => 'United States 1GB 7Days',
                'price' => 1.80,
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
            'amount_charged' => 1.80,
            'esim_details' => [
                'qr_code_url' => 'https://example.test/qr.png',
                'activation_url' => 'https://example.test/activate',
                'iccid' => '8901234567890123456',
                'esim_status' => 'active',
            ],
        ];
    }

    protected function fakeSuccessfulProvider(): void
    {
        Http::fake([
            '*/esim-packages*' => Http::response($this->packagePayload(), 200),
            '*/clients' => Http::response(['success' => true, 'client_id' => 123], 200),
            '*/orders' => Http::response($this->orderPayload(), 200),
        ]);
    }

    public function test_authenticated_user_can_purchase_an_esim(): void
    {
        $this->fakeSuccessfulProvider();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/user/esim/orders', [
            'package_code' => 'PHAJHEAYP',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => __('api.esim.purchased'),
                'errors' => null,
                'data' => [
                    'order' => [
                        'package_code' => 'PHAJHEAYP',
                        'package_name' => 'United States 1GB 7Days',
                        'location' => 'US',
                        'data_volume' => '1GB',
                        'duration' => 7,
                        'price' => 1.8,
                        'list_price' => 1.89,
                        'discount_amount' => 0.09,
                        'discount_percentage' => 5,
                        'status' => 'active',
                    ],
                    'esim' => [
                        'service_id' => 789,
                        'iccid' => '8901234567890123456',
                        'qr_code_url' => 'https://example.test/qr.png',
                        'activation_url' => 'https://example.test/activate',
                        'status' => 'active',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('esim_orders', [
            'package_code' => 'PHAJHEAYP',
            'provider_cost' => 1.80,
            'markup_percentage' => 5.00,
            'markup_amount' => 0.09,
            'customer_price' => 1.89,
            'charged_amount' => 1.80,
            'discount_amount' => 0.09,
            'order_status' => EsimOrder::STATUS_ACTIVE,
            'resellportal_service_id' => 789,
        ]);
        $this->assertDatabaseHas('esim_order_details', [
            'service_id' => 789,
            'iccid' => '8901234567890123456',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_an_order(): void
    {
        $this->postJson('/api/user/esim/orders', [
            'package_code' => 'PHAJHEAYP',
        ])->assertUnauthorized();
    }

    public function test_invalid_package_code_is_rejected(): void
    {
        Http::fake([
            '*/esim-packages*' => Http::response($this->packagePayload(), 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/user/esim/orders', [
            'package_code' => 'MISSING',
        ])->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => __('api.esim.package_not_found'),
                'data' => null,
            ]);

        $this->assertDatabaseCount('esim_orders', 0);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/orders') && $request->method() === 'POST');
    }

    public function test_frontend_cannot_manipulate_prices_or_ids(): void
    {
        $this->fakeSuccessfulProvider();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/user/esim/orders', [
            'package_code' => 'PHAJHEAYP',
            'price' => 0.01,
            'provider_cost' => 0.01,
            'customer_price' => 999,
            'user_id' => 'someone-else',
            'client_id' => 999,
            'service_id' => 1,
            'resellportal_client_id' => 999,
        ])->assertCreated()
            ->assertJsonPath('data.order.price', 1.8)
            ->assertJsonPath('data.esim.service_id', 789);

        $order = EsimOrder::query()->first();
        $this->assertEquals(1.80, (float) $order->provider_cost);
        $this->assertEquals(5.00, (float) $order->markup_percentage);
        $this->assertEquals(0.09, (float) $order->markup_amount);
        $this->assertEquals(1.89, (float) $order->customer_price);
        $this->assertSame('123', $order->resellportal_client_id);
        $this->assertSame('789', $order->resellportal_service_id);
    }

    public function test_resellportal_client_is_created_once_and_reused(): void
    {
        $this->fakeSuccessfulProvider();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();
        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $this->assertSame('123', $user->fresh()->resellportal_client_id);
        $this->assertSame(1, User::query()->whereNotNull('resellportal_client_id')->count());
        $this->assertSame(1, $this->recordedPostCount('clients'));
    }

    public function test_existing_resellportal_client_is_reused(): void
    {
        $this->fakeSuccessfulProvider();
        $user = User::factory()->create(['resellportal_client_id' => 555]);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated();

        $this->assertSame('555', EsimOrder::query()->first()->resellportal_client_id);
        Http::assertNotSent(fn ($request) => str_ends_with(strtok($request->url(), '?'), '/clients'));
    }

    public function test_provider_failure_does_not_mark_order_active(): void
    {
        Http::fake([
            '*/esim-packages*' => Http::response($this->packagePayload(), 200),
            '*/clients' => Http::response(['success' => true, 'client_id' => 123], 200),
            '*/orders' => Http::response(['success' => false, 'message' => 'Insufficient wallet balance'], 400),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertStatus(502)
            ->assertJson([
                'success' => false,
                'message' => __('api.esim.provisioning_failed'),
                'data' => null,
            ])
            ->assertJsonMissing(['Insufficient wallet balance']);

        $this->assertDatabaseHas('esim_orders', [
            'order_status' => EsimOrder::STATUS_FAILED,
            'package_code' => 'PHAJHEAYP',
        ]);
        $this->assertDatabaseCount('esim_order_details', 0);
    }

    public function test_provider_timeout_does_not_mark_order_active(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'esim-packages')) {
                return Http::response($this->packagePayload(), 200);
            }

            if (str_contains($request->url(), 'clients')) {
                return Http::response(['success' => true, 'client_id' => 123], 200);
            }

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertStatus(502)
            ->assertJsonPath('message', __('api.esim.provisioning_failed'));

        $this->assertDatabaseHas('esim_orders', ['order_status' => EsimOrder::STATUS_FAILED]);
    }

    public function test_duplicate_request_is_idempotent(): void
    {
        $this->fakeSuccessfulProvider();
        Sanctum::actingAs(User::factory()->create());

        $headers = ['Idempotency-Key' => 'order-abc-123'];

        $first = $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'], $headers)->assertCreated();
        $second = $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'], $headers)->assertCreated();

        $this->assertSame($first->json('data.order.id'), $second->json('data.order.id'));
        $this->assertDatabaseCount('esim_orders', 1);
        $this->assertDatabaseCount('esim_order_details', 1);
        $this->assertSame(1, $this->recordedPostCount('orders'));
    }

    public function test_user_cannot_access_another_users_order(): void
    {
        $this->fakeSuccessfulProvider();
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $orderId = $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->json('data.order.id');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/esim/orders/'.$orderId)
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => __('api.esim.unauthorized_order'),
            ]);
    }

    public function test_owner_can_view_own_order(): void
    {
        $this->fakeSuccessfulProvider();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $orderId = $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->json('data.order.id');

        $this->getJson('/api/user/esim/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.order.id', $orderId)
            ->assertJsonPath('data.esim.service_id', 789);
    }

    public function test_payment_failure_does_not_provision_esim(): void
    {
        $this->fakeSuccessfulProvider();

        $this->mock(EsimPaymentGatewayInterface::class, function ($mock) {
            $mock->shouldReceive('settle')->once()->andReturn(EsimPaymentResult::failed());
        });

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertStatus(402)
            ->assertJsonPath('message', __('api.esim.payment_failed'));

        $this->assertDatabaseHas('esim_orders', [
            'order_status' => EsimOrder::STATUS_FAILED,
            'payment_status' => EsimOrder::PAYMENT_FAILED,
        ]);
        $this->assertDatabaseCount('esim_order_details', 0);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/orders'));
    }

    public function test_test_mode_provider_ids_are_persisted_without_leaking_charge_flags(): void
    {
        Http::fake([
            '*/esim-packages*' => Http::response($this->packagePayload(), 200),
            '*/clients' => Http::response([
                'success' => true,
                'client_id' => 'test_cli_123',
                'would_charge' => 0,
                'test_mode' => true,
            ], 200),
            '*/orders' => Http::response([
                'success' => true,
                'service_id' => 'test_svc_789',
                'would_charge' => 1.80,
                'test_mode' => true,
                'esim_details' => [
                    'qr_code_url' => 'https://example.test/qr.png',
                    'activation_url' => 'https://example.test/activate',
                    'iccid' => '8901234567890123456',
                    'esim_status' => 'active',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP']);

        $response->assertCreated()
            ->assertJsonPath('data.esim.service_id', 'test_svc_789')
            ->assertJsonMissingPath('data.would_charge')
            ->assertJsonMissingPath('data.test_mode')
            ->assertJsonMissingPath('data.esim.would_charge')
            ->assertJsonMissingPath('data.esim.test_mode');

        $this->assertSame('test_cli_123', $user->fresh()->resellportal_client_id);
        $this->assertDatabaseHas('esim_orders', [
            'resellportal_client_id' => 'test_cli_123',
            'resellportal_service_id' => 'test_svc_789',
        ]);
        $this->assertDatabaseHas('esim_order_details', [
            'service_id' => 'test_svc_789',
        ]);
    }

    public function test_credentials_never_appear_in_response_or_logs(): void
    {
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logs) {
            $logs[] = $event->message.' '.json_encode($event->context);
        });

        $this->fakeSuccessfulProvider();
        Sanctum::actingAs(User::factory()->create());

        $content = $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->getContent();

        $this->assertStringNotContainsString('test-api-key', $content);
        $this->assertStringNotContainsString('test-api-secret', $content);
        $this->assertStringNotContainsString('X-API-Key', $content);

        foreach ($logs as $line) {
            $this->assertStringNotContainsString('test-api-key', $line);
            $this->assertStringNotContainsString('test-api-secret', $line);
        }
    }

    protected function recordedPostCount(string $pathSegment): int
    {
        return collect(Http::recorded())
            ->filter(function (array $pair) use ($pathSegment) {
                $request = $pair[0];

                return $request->method() === 'POST' && str_contains($request->url(), '/'.$pathSegment);
            })
            ->count();
    }

    public function test_purchase_message_is_localized(): void
    {
        $this->fakeSuccessfulProvider();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/user/esim/orders?lang=ru', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated()
            ->assertJsonPath('message', trans('api.esim.purchased', [], 'ru'));
    }
}
