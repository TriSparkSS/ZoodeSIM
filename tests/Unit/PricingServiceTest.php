<?php

namespace Tests\Unit;

use App\Exceptions\PricingUnavailableException;
use App\Models\PricingSlab;
use App\Services\Pricing\PricingService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PricingSlab::factory()->create([
            'min_amount' => '40.00',
            'max_amount' => '50.00',
            'percentage' => '5.00',
            'priority' => 20,
            'is_active' => true,
        ]);
        PricingSlab::factory()->create([
            'min_amount' => '50.00',
            'max_amount' => '100.00',
            'percentage' => '4.00',
            'priority' => 30,
            'is_active' => true,
        ]);
        PricingSlab::factory()->create([
            'min_amount' => '100.00',
            'max_amount' => '200.00',
            'percentage' => '3.00',
            'priority' => 40,
            'is_active' => true,
        ]);
    }

    public function test_forty_dollar_package_gets_five_percent_markup(): void
    {
        $quote = app(PricingService::class)->quoteFromProviderCost('40.00');

        $this->assertSame(4000, $quote->providerCost->cents);
        $this->assertSame('5.00', $quote->markupPercentage);
        $this->assertSame(200, $quote->markupAmount->cents);
        $this->assertSame(4200, $quote->customerPrice->cents);
        $this->assertSame('42.00', $quote->customerPrice->toDecimal());
    }

    public function test_fifty_dollar_boundary_uses_the_next_slab(): void
    {
        $quote = app(PricingService::class)->quoteFromProviderCost('50.00');

        $this->assertSame('4.00', $quote->markupPercentage);
        $this->assertSame(200, $quote->markupAmount->cents);
        $this->assertSame(5200, $quote->customerPrice->cents);
    }

    public function test_one_hundred_dollar_boundary_uses_the_three_percent_slab(): void
    {
        $quote = app(PricingService::class)->quoteFromProviderCost('100.00');

        $this->assertSame('3.00', $quote->markupPercentage);
        $this->assertSame(300, $quote->markupAmount->cents);
        $this->assertSame(10300, $quote->customerPrice->cents);
    }

    public function test_two_hundred_dollar_exclusive_max_has_no_slab(): void
    {
        $this->expectException(PricingUnavailableException::class);

        app(PricingService::class)->quoteFromProviderCost('200.00');
    }

    public function test_amount_just_below_two_hundred_uses_three_percent(): void
    {
        $quote = app(PricingService::class)->quoteFromProviderCost('199.99');

        $this->assertSame('3.00', $quote->markupPercentage);
        $this->assertSame(600, $quote->markupAmount->cents);
        $this->assertSame(20599, $quote->customerPrice->cents);
    }

    public function test_markup_calculation_uses_integer_cents(): void
    {
        $quote = app(PricingService::class)->quoteFromProviderCost('50.00');

        $this->assertIsInt($quote->providerCost->cents);
        $this->assertIsInt($quote->markupAmount->cents);
        $this->assertIsInt($quote->customerPrice->cents);
        $this->assertSame($quote->providerCost->cents + $quote->markupAmount->cents, $quote->customerPrice->cents);
    }

    public function test_rounding_is_half_up_in_cents(): void
    {
        PricingSlab::factory()->create([
            'min_amount' => '0.00',
            'max_amount' => '40.00',
            'percentage' => '5.00',
            'priority' => 10,
            'is_active' => true,
        ]);

        $quote = app(PricingService::class)->quoteFromProviderCost('1.70');

        $this->assertSame(170, $quote->providerCost->cents);
        $this->assertSame(9, $quote->markupAmount->cents);
        $this->assertSame(179, $quote->customerPrice->cents);
        $this->assertSame('1.79', $quote->customerPrice->toDecimal());
    }

    public function test_no_floating_point_financial_errors(): void
    {
        $quote = app(PricingService::class)->quoteFromProviderCost('59.99');

        $this->assertSame(5999, $quote->providerCost->cents);
        $this->assertSame(240, $quote->markupAmount->cents);
        $this->assertSame(6239, $quote->customerPrice->cents);
        $this->assertSame('62.39', $quote->customerPrice->toDecimal());
    }

    public function test_no_matching_slab_throws_controlled_error(): void
    {
        try {
            app(PricingService::class)->quoteFromProviderCost('0.50');
            $this->fail('Expected PricingUnavailableException');
        } catch (PricingUnavailableException $e) {
            $this->assertSame('api.esim.pricing_unavailable', $e->translationKey);
            $this->assertSame(__('api.esim.pricing_unavailable'), $e->userMessage());
            $this->assertSame(422, $e->httpStatus);
        }
    }

    public function test_inactive_slab_is_ignored(): void
    {
        PricingSlab::query()->where('min_amount', '40.00')->update(['is_active' => false]);
        PricingService::forgetCache();

        $this->expectException(PricingUnavailableException::class);

        app(PricingService::class)->quoteFromProviderCost('40.00');
    }

    public function test_priority_selects_one_slab_when_ranges_overlap_in_data(): void
    {
        PricingSlab::factory()->create([
            'min_amount' => '40.00',
            'max_amount' => '60.00',
            'percentage' => '9.00',
            'priority' => 1,
            'is_active' => true,
        ]);
        PricingService::forgetCache();

        $quote = app(PricingService::class)->quoteFromProviderCost('45.00');

        $this->assertSame('9.00', $quote->markupPercentage);
        $this->assertSame(405, $quote->markupAmount->cents);
        $this->assertSame(4905, $quote->customerPrice->cents);
    }

    public function test_stale_incomplete_class_cache_is_rebuilt(): void
    {
        $incomplete = unserialize(serialize(new \stdClass), ['allowed_classes' => false]);

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $incomplete);

        Cache::put((string) config('pricing.cache_key'), $incomplete);

        $slabs = app(PricingService::class)->activeSlabs();

        $this->assertInstanceOf(Collection::class, $slabs);
        $this->assertTrue($slabs->isNotEmpty());
        $this->assertTrue($slabs->every(fn (mixed $slab): bool => $slab instanceof PricingSlab));

        $quote = app(PricingService::class)->quoteFromProviderCost('40.00');

        $this->assertSame('5.00', $quote->markupPercentage);
    }

    public function test_money_rejects_invalid_amounts(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromDecimal('12.345', 'USD');
    }
}
