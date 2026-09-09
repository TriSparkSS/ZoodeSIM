<?php

namespace Tests\Feature;

use App\Exceptions\ResellPortalException;
use App\Services\ResellPortal\Contracts\ResellPortalClientInterface;
use App\Services\ResellPortal\ResellPortalConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResellPortalClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_connection_succeeds_against_balance_endpoint(): void
    {
        Http::fake([
            '*/balance' => Http::response([
                'success' => true,
                'balance' => 150.00,
                'currency' => 'USD',
            ], 200),
        ]);

        $payload = app(ResellPortalConnectionService::class)->verify();

        $this->assertTrue($payload['success']);
        $this->assertEquals(150.00, $payload['balance']);

        Http::assertSent(function ($request) {
            return str_ends_with(strtok($request->url(), '?'), '/balance')
                && $request->hasHeader('X-API-Key', 'test-api-key')
                && $request->hasHeader('X-API-Secret', 'test-api-secret')
                && $request->hasHeader('Content-Type', 'application/json')
                && ! $request->hasHeader('X-RP-Test-Mode')
                && ! str_contains($request->body(), 'test_mode')
                && ! str_contains($request->body(), 'test-api-key')
                && ! str_contains($request->body(), 'test-api-secret');
        });
    }

    public function test_authentication_failure_is_wrapped_without_exposing_credentials(): void
    {
        Http::fake([
            '*/balance' => Http::response(['success' => false, 'message' => 'Invalid API Key'], 401),
        ]);

        try {
            app(ResellPortalConnectionService::class)->verify();
            $this->fail('Expected ResellPortalException');
        } catch (ResellPortalException $e) {
            $this->assertSame('authentication', $e->reason);
            $this->assertSame(__('api.esim.unavailable'), $e->userMessage());
            $this->assertStringNotContainsString('test-api-key', $e->getMessage());
            $this->assertStringNotContainsString('test-api-secret', $e->getMessage());
            $this->assertStringNotContainsString('Invalid API Key', $e->userMessage());
        }
    }

    public function test_timeout_is_wrapped_as_connection_failure(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        try {
            app(ResellPortalConnectionService::class)->verify();
            $this->fail('Expected ResellPortalException');
        } catch (ResellPortalException $e) {
            $this->assertSame('timeout', $e->reason);
        }
    }

    public function test_client_error_responses_are_wrapped(): void
    {
        Http::fake([
            '*/balance' => Http::response(['success' => false], 400),
        ]);

        try {
            app(ResellPortalConnectionService::class)->verify();
            $this->fail('Expected ResellPortalException');
        } catch (ResellPortalException $e) {
            $this->assertSame('client_error', $e->reason);
        }
    }

    public function test_server_error_responses_are_wrapped(): void
    {
        Http::fake([
            '*/balance' => Http::response('Internal Server Error', 500),
        ]);

        try {
            app(ResellPortalConnectionService::class)->verify();
            $this->fail('Expected ResellPortalException');
        } catch (ResellPortalException $e) {
            $this->assertSame('server_error', $e->reason);
        }
    }

    public function test_mutating_calls_send_test_mode_when_livemode_is_false(): void
    {
        Http::fake([
            '*/clients' => Http::response(['success' => true, 'client_id' => 'test_cli_1'], 200),
            '*/orders' => Http::response(['success' => true, 'service_id' => 'test_svc_1', 'would_charge' => 1.80], 200),
            '*/clients/*' => Http::response(['success' => true], 200),
            '*/services/*' => Http::response(['success' => true], 200),
        ]);

        $client = app(ResellPortalClientInterface::class);

        $client->createClient('Ada', 'ada@example.com');
        $client->createEsimOrder('test_cli_1', 'PHAJHEAYP');
        $client->delete('clients/test_cli_1');
        $client->delete('services/test_svc_1');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_ends_with(strtok($request->url(), '?'), '/clients')
                && $request['test_mode'] === true
                && $request->hasHeader('X-RP-Test-Mode', '1');
        });

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_ends_with(strtok($request->url(), '?'), '/orders')
                && $request['test_mode'] === true
                && $request->hasHeader('X-RP-Test-Mode', '1');
        });

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), '/clients/test_cli_1')
                && $request->hasHeader('X-RP-Test-Mode', '1')
                && ! str_contains($request->body(), 'test_mode');
        });

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), '/services/test_svc_1')
                && $request->hasHeader('X-RP-Test-Mode', '1')
                && ! str_contains($request->body(), 'test_mode');
        });
    }

    public function test_live_mode_omits_test_flags_on_mutating_calls(): void
    {
        config(['services.resellportal.live_mode' => true]);

        Http::fake([
            '*/clients' => Http::response(['success' => true, 'client_id' => 123], 200),
            '*/orders' => Http::response(['success' => true, 'service_id' => 789], 200),
        ]);

        $client = app(ResellPortalClientInterface::class);
        $client->createClient('Ada', 'ada@example.com');
        $client->createEsimOrder('123', 'PHAJHEAYP');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_ends_with(strtok($request->url(), '?'), '/clients')
                && ! $request->hasHeader('X-RP-Test-Mode')
                && ! array_key_exists('test_mode', $request->data());
        });

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_ends_with(strtok($request->url(), '?'), '/orders')
                && ! $request->hasHeader('X-RP-Test-Mode')
                && ! array_key_exists('test_mode', $request->data())
                && $request['client_id'] === 123;
        });
    }

    public function test_catalog_and_balance_never_send_test_mode(): void
    {
        Http::fake([
            '*/esim-packages*' => Http::response(['success' => true, 'packages' => []], 200),
            '*/balance' => Http::response(['success' => true, 'balance' => 10, 'currency' => 'USD'], 200),
        ]);

        $client = app(ResellPortalClientInterface::class);
        $client->getEsimPackages('US');
        $client->getBalance();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/esim-packages')
                && ! $request->hasHeader('X-RP-Test-Mode')
                && ! str_contains($request->body(), 'test_mode');
        });

        Http::assertSent(function ($request) {
            return str_ends_with(strtok($request->url(), '?'), '/balance')
                && ! $request->hasHeader('X-RP-Test-Mode')
                && ! str_contains($request->body(), 'test_mode');
        });
    }
}
