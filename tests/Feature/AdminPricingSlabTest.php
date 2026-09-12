<?php

namespace Tests\Feature;

use App\Livewire\Admin\PricingSlabs;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\Partner;
use App\Models\PricingSlab;
use App\Models\User;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPricingSlabTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Pricing Admin',
            'email' => 'pricing-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }

    protected function partner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Pricing Partner',
            'email' => 'pricing-partner@zoodesim.test',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }

    public function test_admin_can_list_slabs(): void
    {
        PricingSlab::factory()->create([
            'min_amount' => '200.00',
            'max_amount' => '300.00',
            'percentage' => '2.00',
            'priority' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(PricingSlabs::class)
            ->assertSee('$200.00')
            ->assertSee('$300.00')
            ->assertSee('2%');
    }

    public function test_admin_can_create_slab(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        Livewire::test(PricingSlabs::class)
            ->set('formMinAmount', '200.00')
            ->set('formMaxAmount', '300.00')
            ->set('formPercentage', '2.00')
            ->set('formPriority', '50')
            ->set('formIsActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pricing_slabs', [
            'min_amount' => '200.00',
            'max_amount' => '300.00',
            'percentage' => '2.00',
            'priority' => 50,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_CREATED,
            'guard' => 'admin',
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_can_update_slab(): void
    {
        $admin = $this->admin();
        $slab = PricingSlab::factory()->create([
            'min_amount' => '200.00',
            'max_amount' => '300.00',
            'percentage' => '2.00',
            'priority' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(PricingSlabs::class)
            ->call('openEditModal', $slab->id)
            ->set('formPercentage', '2.50')
            ->set('formPriority', '60')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pricing_slabs', [
            'id' => $slab->id,
            'percentage' => '2.50',
            'priority' => 60,
        ]);
        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_UPDATED,
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_can_activate_and_deactivate_slab(): void
    {
        $admin = $this->admin();
        $slab = PricingSlab::factory()->create([
            'min_amount' => '200.00',
            'max_amount' => '300.00',
            'percentage' => '2.00',
            'priority' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(PricingSlabs::class)
            ->call('toggle', $slab->id);

        $this->assertFalse($slab->fresh()->is_active);
        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_DEACTIVATED,
            'authenticatable_id' => $admin->id,
        ]);

        Livewire::test(PricingSlabs::class)
            ->call('toggle', $slab->id);

        $this->assertTrue($slab->fresh()->is_active);
        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_ACTIVATED,
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_can_delete_slab(): void
    {
        $admin = $this->admin();
        $slab = PricingSlab::factory()->create([
            'min_amount' => '200.00',
            'max_amount' => '300.00',
            'percentage' => '2.00',
            'priority' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(PricingSlabs::class)
            ->call('delete', $slab->id);

        $this->assertDatabaseMissing('pricing_slabs', ['id' => $slab->id]);
        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_DELETED,
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_overlapping_active_slabs_are_prevented(): void
    {
        PricingSlab::factory()->create([
            'min_amount' => '40.00',
            'max_amount' => '50.00',
            'percentage' => '5.00',
            'priority' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(PricingSlabs::class)
            ->set('formMinAmount', '45.00')
            ->set('formMaxAmount', '60.00')
            ->set('formPercentage', '6.00')
            ->set('formPriority', '50')
            ->call('save');

        $this->assertDatabaseCount('pricing_slabs', 1);
    }

    public function test_adjacent_half_open_ranges_are_allowed(): void
    {
        PricingSlab::factory()->create([
            'min_amount' => '40.00',
            'max_amount' => '50.00',
            'percentage' => '5.00',
            'priority' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(PricingSlabs::class)
            ->set('formMinAmount', '50.00')
            ->set('formMaxAmount', '100.00')
            ->set('formPercentage', '4.00')
            ->set('formPriority', '51')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('pricing_slabs', 2);
    }

    public function test_invalid_range_and_percentage_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(PricingSlabs::class)
            ->set('formMinAmount', '80.00')
            ->set('formMaxAmount', '80.00')
            ->set('formPercentage', '2.00')
            ->set('formPriority', '50')
            ->call('save');

        $this->assertDatabaseCount('pricing_slabs', 0);

        Livewire::test(PricingSlabs::class)
            ->set('formMinAmount', '200.00')
            ->set('formMaxAmount', '300.00')
            ->set('formPercentage', '101')
            ->set('formPriority', '50')
            ->call('save')
            ->assertHasErrors(['formPercentage']);
    }

    public function test_admin_can_preview_price_without_creating_an_order(): void
    {
        PricingSlab::factory()->create([
            'min_amount' => '40.00',
            'max_amount' => '50.00',
            'percentage' => '5.00',
            'priority' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(PricingSlabs::class)
            ->set('previewCost', '40.00')
            ->call('preview')
            ->assertSet('previewResult.provider_cost', '$40.00')
            ->assertSet('previewResult.markup_percentage', '5.00%')
            ->assertSet('previewResult.markup_amount', '$2.00')
            ->assertSet('previewResult.customer_price', '$42.00')
            ->assertSet('previewError', null);

        $this->assertDatabaseCount('esim_orders', 0);
    }

    public function test_pricing_cache_is_invalidated_after_changes(): void
    {
        Cache::put((string) config('pricing.cache_key'), collect());

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(PricingSlabs::class)
            ->set('formMinAmount', '200.00')
            ->set('formMaxAmount', '300.00')
            ->set('formPercentage', '2.00')
            ->set('formPriority', '50')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(Cache::has((string) config('pricing.cache_key')));

        $quote = app(PricingService::class)->quoteFromProviderCost('200.00');
        $this->assertSame('2.00', $quote->markupPercentage);
    }

    public function test_admin_http_pricing_routes_are_removed(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->getJson('/api/admin/pricing/slabs')
            ->assertNotFound();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/admin/pricing/slabs', [])->assertNotFound();
    }

    public function test_partner_cannot_open_admin_pricing_page(): void
    {
        $this->actingAs($this->partner(), 'partner')
            ->get(route('admin.pricing-slabs'))
            ->assertForbidden();
    }

    public function test_unauthenticated_guest_cannot_open_admin_pricing_page(): void
    {
        $this->get(route('admin.pricing-slabs'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_livewire_page_can_create_and_preview(): void
    {
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(PricingSlabs::class)
            ->set('formMinAmount', '10.00')
            ->set('formMaxAmount', '20.00')
            ->set('formPercentage', '5.00')
            ->set('formPriority', '5')
            ->set('formIsActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pricing_slabs', [
            'min_amount' => '10.00',
            'max_amount' => '20.00',
            'percentage' => '5.00',
        ]);

        Livewire::test(PricingSlabs::class)
            ->set('previewCost', '10.00')
            ->call('preview')
            ->assertSet('previewResult.customer_price', '$10.50')
            ->assertSet('previewError', null);
    }
}
