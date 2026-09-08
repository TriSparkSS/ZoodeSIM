<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use App\Models\User;
use App\Services\Logging\Contracts\ApiLoggerServiceInterface;
use App\Services\ResellPortal\ResellPortalConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsDefaultPricingSlabs;
use Tests\TestCase;

class ApiLoggingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDefaultPricingSlabs;

    public function test_internal_api_success_is_logged_without_tokens(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/user/profile')
            ->assertOk();

        $log = ApiLog::query()
            ->where('type', ApiLog::TYPE_INTERNAL)
            ->where('endpoint', '/api/user/profile')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('GET', $log->method);
        $this->assertSame(ApiLog::SERVICE_PORTAL, $log->service);
        $this->assertSame(200, $log->response_status);
        $this->assertSame($user->id, $log->user_id);
        $this->assertNotNull($log->ip_address);
        $this->assertGreaterThanOrEqual(0, $log->response_time_ms);
        $this->assertNull($log->error_message);
        $this->assertStringNotContainsString('test-api-key', json_encode($log->toArray()));
    }

    public function test_internal_login_redacts_password(): void
    {
        $user = User::factory()->create([
            'email' => 'logger@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/user/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $log = ApiLog::query()
            ->where('type', ApiLog::TYPE_INTERNAL)
            ->where('endpoint', '/api/user/login')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('********', $log->request_body['password'] ?? null);
        $this->assertSame('logger@example.com', $log->request_body['email'] ?? null);
        $this->assertSame('********', data_get($log->response_body, 'data.token'));
        $encoded = json_encode($log->toArray());
        $this->assertStringNotContainsString('password123', (string) $encoded);
    }

    public function test_failed_internal_request_is_logged(): void
    {
        $this->postJson('/api/user/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $log = ApiLog::query()
            ->where('endpoint', '/api/user/login')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(401, $log->response_status);
        $this->assertTrue($log->isFailed());
        $this->assertSame('********', $log->request_body['password'] ?? null);
    }

    public function test_resellportal_success_is_logged_with_redacted_headers(): void
    {
        $this->seedDefaultPricingSlabs();
        Http::fake([
            '*/esim-packages' => Http::response([
                'success' => true,
                'packages' => [[
                    'package_code' => 'PHAJHEAYP',
                    'name' => 'United States 1GB 7Days',
                    'price' => 1.80,
                    'location' => 'US',
                ]],
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/esim/packages')->assertOk();

        $log = ApiLog::query()
            ->where('type', ApiLog::TYPE_THIRD_PARTY)
            ->where('service', ApiLog::SERVICE_RESELLPORTAL)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('GET', $log->method);
        $this->assertSame('/esim-packages', $log->endpoint);
        $this->assertSame(200, $log->response_status);
        $this->assertSame('********', $log->request_headers['X-API-Key'] ?? null);
        $this->assertSame('********', $log->request_headers['X-API-Secret'] ?? null);
        $this->assertGreaterThanOrEqual(0, $log->response_time_ms);
        $this->assertStringNotContainsString('test-api-key', json_encode($log->toArray()));
        $this->assertStringNotContainsString('test-api-secret', json_encode($log->toArray()));
    }

    public function test_resellportal_timeout_is_logged(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        try {
            app(ResellPortalConnectionService::class)->verify();
            $this->fail('Expected timeout');
        } catch (\Throwable) {
            // expected
        }

        $log = ApiLog::query()
            ->where('type', ApiLog::TYPE_THIRD_PARTY)
            ->where('service', ApiLog::SERVICE_RESELLPORTAL)
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->response_status);
        $this->assertNotNull($log->error_message);
        $this->assertStringContainsString('timeout', strtolower((string) $log->error_message));
        $this->assertGreaterThanOrEqual(0, $log->response_time_ms);
    }

    public function test_resellportal_failed_response_is_logged(): void
    {
        Http::fake([
            '*/balance' => Http::response(['success' => false, 'message' => 'Invalid API Key'], 401),
        ]);

        try {
            app(ResellPortalConnectionService::class)->verify();
            $this->fail('Expected failure');
        } catch (\Throwable) {
            // expected
        }

        $log = ApiLog::query()
            ->where('type', ApiLog::TYPE_THIRD_PARTY)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(401, $log->response_status);
        $this->assertNotNull($log->error_message);
        $this->assertSame('********', $log->request_headers['X-API-Key'] ?? null);
        $this->assertStringNotContainsString('test-api-key', json_encode($log->toArray()));
    }

    public function test_large_response_bodies_are_truncated(): void
    {
        config(['api_logging.max_body_bytes' => 200]);

        Http::fake([
            '*/balance' => Http::response([
                'success' => true,
                'payload' => str_repeat('x', 5000),
            ], 200),
        ]);

        app(ResellPortalConnectionService::class)->verify();

        $log = ApiLog::query()->where('service', ApiLog::SERVICE_RESELLPORTAL)->first();

        $this->assertNotNull($log);
        $this->assertTrue((bool) ($log->response_body['_truncated'] ?? false));
        $this->assertArrayHasKey('_preview', $log->response_body);
    }

    public function test_logging_failure_does_not_break_the_api(): void
    {
        $this->mock(ApiLoggerServiceInterface::class, function ($mock) {
            $mock->shouldReceive('logInternalRequest')->andThrow(new \RuntimeException('logger down'));
            $mock->shouldReceive('logHttp')->andThrow(new \RuntimeException('logger down'));
            $mock->shouldReceive('elapsedMs')->andReturn(1);
            $mock->shouldReceive('log')->andThrow(new \RuntimeException('logger down'));
        });

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/user/profile')->assertOk();
    }

    public function test_order_reference_is_attached_to_provider_logs(): void
    {
        $this->seedDefaultPricingSlabs();
        Http::fake([
            '*/esim-packages*' => Http::response([
                'success' => true,
                'packages' => [[
                    'package_code' => 'PHAJHEAYP',
                    'name' => 'United States 1GB 7Days',
                    'price' => 1.80,
                    'location' => 'US',
                ]],
            ], 200),
            '*/clients' => Http::response(['success' => true, 'client_id' => 123], 200),
            '*/orders' => Http::response([
                'success' => true,
                'service_id' => 789,
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

        $orderId = $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated()
            ->json('data.order.id');

        $providerOrderLog = ApiLog::query()
            ->where('type', ApiLog::TYPE_THIRD_PARTY)
            ->where('endpoint', '/orders')
            ->first();

        $this->assertNotNull($providerOrderLog);
        $this->assertSame('order', $providerOrderLog->reference_type);
        $this->assertSame($orderId, $providerOrderLog->reference_id);
        $this->assertSame($user->id, $providerOrderLog->user_id);
    }
}
