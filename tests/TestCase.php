<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
        ]);
    }
}
