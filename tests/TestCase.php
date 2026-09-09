<?php

namespace Tests;

use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.resellportal.base_url' => 'https://panel.resellportal.com/wp-json/resellportal/v1/',
            'services.resellportal.api_key' => 'test-api-key',
            'services.resellportal.api_secret' => 'test-api-secret',
            'services.resellportal.timeout' => 5,
            'services.resellportal.balance_cache_ttl' => 60,
        ]);
    }

    protected function fakeResellPortalClientCreate(int $startingClientId = 123): void
    {
        $nextId = $startingClientId;

        Http::fake([
            '*/clients' => function () use (&$nextId) {
                return Http::response(['success' => true, 'client_id' => $nextId++], 200);
            },
        ]);
    }

    /**
     * @param  Closure|array<string, mixed>  $callback
     */
    protected function replaceHttpFake(Closure|array $callback): void
    {
        $factory = new HttpFactory;
        $this->app->instance(HttpFactory::class, $factory);
        Http::swap($factory);
        Http::fake($callback);
    }
}
