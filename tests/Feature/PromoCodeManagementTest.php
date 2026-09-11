<?php

namespace Tests\Feature;

use App\Livewire\Admin\PromoCodes;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Services\Promo\PromoCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PromoCodeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_promo_code_for_partner(): void
    {
        $partner = $this->makePartner('Dilshod Karimov');

        Livewire::test(PromoCodes::class)
            ->call('openCreateModal', $partner->id)
            ->set('formCode', 'DILSHOD99')
            ->set('formBonusAmount', '250')
            ->set('formPartnerReward', '2.00')
            ->set('formType', 'standard')
            ->set('formExpiresAt', now()->addDays(14)->format('Y-m-d'))
            ->set('formMaxUsage', '100')
            ->call('createPromo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('promo_codes', [
            'partner_id' => $partner->id,
            'code' => 'DILSHOD99',
            'bonus_mb' => 250,
            'bonus_type' => PromoCode::BONUS_TYPE_MB,
            'bonus_amount' => 250,
            'max_usage' => 100,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_reward_dollar_promo_code(): void
    {
        $partner = $this->makePartner('Usd Bonus Partner');

        Livewire::test(PromoCodes::class)
            ->call('openCreateModal', $partner->id)
            ->set('formCode', 'USDREW01')
            ->set('formBonusType', PromoCode::BONUS_TYPE_USD)
            ->set('formBonusAmount', '5.00')
            ->set('formPartnerReward', '1.50')
            ->set('formExpiresAt', now()->addDays(14)->format('Y-m-d'))
            ->call('createPromo')
            ->assertHasNoErrors()
            ->assertSee('$5.00');

        $this->assertDatabaseHas('promo_codes', [
            'partner_id' => $partner->id,
            'code' => 'USDREW01',
            'bonus_type' => PromoCode::BONUS_TYPE_USD,
            'bonus_mb' => 0,
            'bonus_amount' => 5.00,
            'is_active' => true,
        ]);
    }

    public function test_assigning_new_code_deactivates_existing_active_codes(): void
    {
        $partner = $this->makePartner('Nodira Saidova');

        $old = PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'NODIRA10',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->subDay(),
            'is_active' => true,
            'usage_count' => 5,
            'max_usage' => 5,
        ]);

        Livewire::test(PromoCodes::class)
            ->call('openAssignModal', $partner->id)
            ->set('formCode', 'NODIRA88')
            ->set('formBonusAmount', '200')
            ->set('formPartnerReward', '1.50')
            ->set('formExpiresAt', now()->addDays(30)->format('Y-m-d'))
            ->call('assignPromo')
            ->assertHasNoErrors();

        $this->assertFalse($old->fresh()->is_active);
        $this->assertDatabaseHas('promo_codes', [
            'partner_id' => $partner->id,
            'code' => 'NODIRA88',
            'is_active' => true,
        ]);
    }

    public function test_promo_lifecycle_status_reflects_expiry_and_usage_limit(): void
    {
        $partner = $this->makePartner('Aziza Test');

        $expired = PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'EXPIRE01',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->subDay(),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $exhausted = PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'LIMIT001',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'single',
            'expires_at' => now()->addDays(10),
            'is_active' => true,
            'usage_count' => 10,
            'max_usage' => 10,
        ]);

        $this->assertSame('expired', $expired->lifecycleStatus());
        $this->assertFalse($expired->isCurrentlyUsable());
        $this->assertSame('exhausted', $exhausted->lifecycleStatus());
        $this->assertFalse($exhausted->isCurrentlyUsable());
    }

    public function test_service_rejects_duplicate_promo_codes(): void
    {
        $partner = $this->makePartner('Duplicate Check');

        PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'UNIQUE01',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(PromoCodeService::class)->assignToPartner($partner, 'UNIQUE01');
    }

    protected function makePartner(string $name): Partner
    {
        return Partner::query()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'password' => 'password123',
            'social_contacts' => [
                'telegram' => null,
                'instagram' => null,
                'twitter' => null,
            ],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }
}
