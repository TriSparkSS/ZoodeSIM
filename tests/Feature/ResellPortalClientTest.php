<?php

namespace Tests\Feature;

use App\Exceptions\ResellPortalException;
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
}
