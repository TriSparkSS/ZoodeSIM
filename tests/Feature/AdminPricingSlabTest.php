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

    /**
     * @return array{min_amount: string, max_amount: string, percentage: string, priority: int, is_active: bool}
     */
    protected function slabPayload(array $overrides = []): array
    {
        return array_merge([
            'min_amount' => '200.00',
            'max_amount' => '300.00',
            'percentage' => '2.00',
            'priority' => 50,
            'is_active' => true,
        ], $overrides);
    }

    public function test_admin_can_list_slabs(): void
    {
        $slab = PricingSlab::factory()->create($this->slabPayload());

        $this->actingAs($this->admin(), 'admin')
            ->getJson('/api/admin/pricing/slabs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('api.admin.pricing.slabs_retrieved'))
            ->assertJsonPath('data.slabs.0.id', $slab->id)
            ->assertJsonPath('data.slabs.0.min_amount', '200.00')
            ->assertJsonPath('data.slabs.0.percentage', '2.00');
    }

    public function test_admin_can_create_slab(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/pricing/slabs', $this->slabPayload())
            ->assertCreated()
            ->assertJsonPath('message', __('api.admin.pricing.slab_created'))
            ->assertJsonPath('data.slab.min_amount', '200.00');

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
        $slab = PricingSlab::factory()->create($this->slabPayload());

        $this->actingAs($admin, 'admin')
            ->putJson('/api/admin/pricing/slabs/'.$slab->id, [
                'percentage' => '2.50',
                'priority' => 60,
            ])
            ->assertOk()
            ->assertJsonPath('data.slab.percentage', '2.50')
            ->assertJsonPath('data.slab.priority', 60);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_UPDATED,
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_can_activate_and_deactivate_slab(): void
    {
        $admin = $this->admin();
        $slab = PricingSlab::factory()->create($this->slabPayload());

        $this->actingAs($admin, 'admin')
            ->putJson('/api/admin/pricing/slabs/'.$slab->id, ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.slab.is_active', false);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_DEACTIVATED,
            'authenticatable_id' => $admin->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->putJson('/api/admin/pricing/slabs/'.$slab->id, ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.slab.is_active', true);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_ACTIVATED,
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_can_delete_slab(): void
    {
        $admin = $this->admin();
        $slab = PricingSlab::factory()->create($this->slabPayload());

        $this->actingAs($admin, 'admin')
            ->deleteJson('/api/admin/pricing/slabs/'.$slab->id)
            ->assertOk()
            ->assertJsonPath('message', __('api.admin.pricing.slab_deleted'));

        $this->assertDatabaseMissing('pricing_slabs', ['id' => $slab->id]);
        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PRICING_SLAB_DELETED,
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_overlapping_active_slabs_are_prevented(): void
    {
        PricingSlab::factory()->create($this->slabPayload([
            'min_amount' => '40.00',
            'max_amount' => '50.00',
            'percentage' => '5.00',
        ]));

        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/pricing/slabs', $this->slabPayload([
                'min_amount' => '45.00',
                'max_amount' => '60.00',
                'percentage' => '6.00',
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('admin.pricing_slabs.validation.overlap'));
    }

    public function test_adjacent_half_open_ranges_are_allowed(): void
    {
        PricingSlab::factory()->create($this->slabPayload([
            'min_amount' => '40.00',
            'max_amount' => '50.00',
            'percentage' => '5.00',
        ]));

        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/pricing/slabs', $this->slabPayload([
                'min_amount' => '50.00',
                'max_amount' => '100.00',
                'percentage' => '4.00',
                'priority' => 51,
            ]))
            ->assertCreated();
    }

    public function test_invalid_range_and_percentage_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/pricing/slabs', $this->slabPayload([
                'min_amount' => '80.00',
                'max_amount' => '80.00',
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('admin.pricing_slabs.validation.max_greater'));

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/pricing/slabs', $this->slabPayload([
                'percentage' => '101',
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('admin.pricing_slabs.validation.percentage_range'));
    }

    public function test_admin_can_preview_price_without_creating_an_order(): void
    {
        PricingSlab::factory()->create($this->slabPayload([
            'min_amount' => '40.00',
            'max_amount' => '50.00',
            'percentage' => '5.00',
        ]));

        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/pricing/preview', ['provider_cost' => '40.00'])
            ->assertOk()
            ->assertJsonPath('data.preview.provider_cost', '40.00')
            ->assertJsonPath('data.preview.markup_percentage', '5.00')
            ->assertJsonPath('data.preview.markup_amount', '2.00')
            ->assertJsonPath('data.preview.customer_price', '42.00')
            ->assertJsonPath('data.preview.currency', 'USD');

        $this->assertDatabaseCount('esim_orders', 0);
    }

    public function test_pricing_cache_is_invalidated_after_changes(): void
    {
        Cache::put((string) config('pricing.cache_key'), collect());

        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/pricing/slabs', $this->slabPayload())
            ->assertCreated();

        $this->assertFalse(Cache::has((string) config('pricing.cache_key')));

        $quote = app(PricingService::class)->quoteFromProviderCost('200.00');
        $this->assertSame('2.00', $quote->markupPercentage);
    }

    public function test_normal_user_cannot_manage_slabs(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/pricing/slabs')->assertUnauthorized();
        $this->postJson('/api/admin/pricing/slabs', $this->slabPayload())->assertUnauthorized();
    }

    public function test_partner_cannot_manage_slabs(): void
    {
        $partner = $this->partner();

        $this->actingAs($partner, 'partner')
            ->getJson('/api/admin/pricing/slabs')
            ->assertUnauthorized();

        $this->actingAs($partner, 'partner')
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
