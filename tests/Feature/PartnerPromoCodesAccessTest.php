<?php

namespace Tests\Feature;

use App\Livewire\Partner\PromoCodes;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerPromoCodesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_promo_codes_page_is_read_only(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Partner One',
            'email' => 'partner1@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $this->actingAs($partner, 'partner');

        $component = Livewire::test(PromoCodes::class);

        $component
            ->assertDontSee(__('partner.promo_codes.create'))
            ->assertDontSee(__('partner.promo_codes.create_modal_title'))
            ->assertSee(__('partner.promo_codes.empty'));

        $this->assertFalse(method_exists(PromoCodes::class, 'openCreateModal'));
    }
}
