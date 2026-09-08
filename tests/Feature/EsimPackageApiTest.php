<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsDefaultPricingSlabs;
use Tests\TestCase;

class EsimPackageApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDefaultPricingSlabs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDefaultPricingSlabs();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function providerPackages(): array
    {
        return [
            [
                'package_code' => 'PHAJHEAYP',
                'name' => 'United States 1GB 7Days',
                'price' => 1.80,
                'location' => 'US',
            ],
            [
                'package_code' => 'INPLAN01',
                'name' => 'India 2GB 15Days',
                'price' => 2.50,
                'location' => 'IN',
            ],
        ];
    }

    public function test_authenticated_user_can_fetch_all_packages(): void
    {
        Http::fake([
            '*/esim-packages' => Http::response([
                'success' => true,
                'packages' => $this->providerPackages(),
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/user/esim/packages');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('api.esim.packages_retrieved'),
                'errors' => null,
            ])
            ->assertJsonPath('data.packages.0.package_code', 'PHAJHEAYP')
            ->assertJsonPath('data.packages.0.name', 'United States 1GB 7Days')
            ->assertJsonPath('data.packages.0.price', 1.8)
            ->assertJsonPath('data.packages.0.list_price', 1.89)
            ->assertJsonPath('data.packages.0.discount_amount', 0.09)
            ->assertJsonPath('data.packages.0.discount_percentage', 5)
            ->assertJsonPath('data.packages.0.currency', 'USD')
            ->assertJsonPath('data.packages.0.location', 'US')
            ->assertJsonCount(2, 'data.packages');

        $this->assertSame(
            ['package_code', 'name', 'price', 'currency', 'location', 'list_price', 'discount_amount', 'discount_percentage'],
            array_keys($response->json('data.packages.0'))
        );
    }

    public function test_country_filter_is_sent_as_location(): void
    {
        Http::fake([
            '*/esim-packages*' => Http::response([
                'success' => true,
                'packages' => [$this->providerPackages()[0]],
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/esim/packages?country=us')
            ->assertOk()
            ->assertJsonPath('data.packages.0.location', 'US');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'esim-packages')
                && $request['location'] === 'US';
        });
    }

    public function test_invalid_country_is_rejected(): void
    {
        Http::fake();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/esim/packages?country=USA')
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => __('api.esim.invalid_country'),
                'data' => null,
            ]);

        Http::assertNothingSent();
    }

    public function test_empty_package_list_returns_success_envelope(): void
    {
        Http::fake([
            '*/esim-packages' => Http::response([
                'success' => true,
                'packages' => [],
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/esim/packages')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('api.esim.packages_retrieved'),
                'data' => ['packages' => []],
                'errors' => null,
            ]);
    }

    public function test_customer_price_is_returned_without_internal_fields(): void
    {
        Http::fake([
            '*/esim-packages' => Http::response([
                'success' => true,
                'packages' => [[
                    'package_code' => 'PHAJHEAYP',
                    'name' => 'United States 1GB 7Days',
                    'price' => 1.80,
                    'location' => 'US',
                    'internal_cost' => 0.90,
                ]],
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $package = $this->getJson('/api/user/esim/packages')->json('data.packages.0');

        $this->assertSame(1.8, $package['price']);
        $this->assertSame(1.89, $package['list_price']);
        $this->assertSame('USD', $package['currency']);
        $this->assertArrayNotHasKey('internal_cost', $package);
        $this->assertArrayNotHasKey('commission', $package);
        $this->assertArrayNotHasKey('markup', $package);
        $this->assertArrayNotHasKey('provider_cost', $package);
        $this->assertArrayNotHasKey('slab_id', $package);
        $this->assertArrayNotHasKey('priority', $package);
    }

    public function test_unauthenticated_access_is_rejected(): void
    {
        $this->getJson('/api/user/esim/packages')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => __('api.unauthenticated'),
                'data' => null,
            ]);
    }

    public function test_provider_failures_return_safe_localized_message(): void
    {
        Http::fake([
            '*/esim-packages' => Http::response(['error' => 'upstream exploded'], 502),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/esim/packages', [
            'Accept-Language' => 'ru',
        ])
            ->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => trans('api.esim.unavailable', [], 'ru'),
                'data' => null,
            ])
            ->assertJsonMissing(['error' => 'upstream exploded'])
            ->assertJsonMissing(['test-api-key'])
            ->assertJsonMissing(['test-api-secret'])
            ->assertJsonMissing(['X-API-Key'])
            ->assertJsonMissing(['X-API-Secret']);
    }

    public function test_credentials_are_never_returned_on_success(): void
    {
        Http::fake([
            '*/esim-packages' => Http::response([
                'success' => true,
                'packages' => [$this->providerPackages()[0]],
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $content = $this->getJson('/api/user/esim/packages')->getContent();

        $this->assertStringNotContainsString('test-api-key', $content);
        $this->assertStringNotContainsString('test-api-secret', $content);
        $this->assertStringNotContainsString('X-API-Key', $content);
        $this->assertStringNotContainsString('X-API-Secret', $content);
        $this->assertStringNotContainsString('RESELLPORTAL', $content);
    }

    public function test_successful_response_uses_locale_query(): void
    {
        Http::fake([
            '*/esim-packages' => Http::response([
                'success' => true,
                'packages' => [],
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/esim/packages?lang=de')
            ->assertOk()
            ->assertJson([
                'message' => trans('api.esim.packages_retrieved', [], 'de'),
            ]);
    }
}
