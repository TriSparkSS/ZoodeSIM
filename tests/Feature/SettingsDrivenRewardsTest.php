<?php

namespace Tests\Feature;

use App\Livewire\Admin\PromoCodes as AdminPromoCodes;
use App\Livewire\Public\ApplyForm;
use App\Models\Partner;
use App\Models\PartnerApplication;
use App\Models\ProgramSetting;
use App\Models\PromoCode;
use App\Services\Partner\PartnerApplicationService;
use App\Services\Promo\PromoCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsDrivenRewardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_uses_program_settings_for_promo_defaults(): void
    {
        $this->putSetting('user_bonus', '350');
        $this->putSetting('registration_reward', '2.25');

        $application = PartnerApplication::query()->create([
            'first_name' => 'Settings',
            'last_name' => 'Partner',
            'email' => 'settings-partner@example.com',
            'phone' => '+15550009999',
            'platforms' => ['telegram'],
            'telegram' => 'https://t.me/settings',
            'instagram' => null,
            'tiktok' => null,
            'youtube' => null,
            'followers' => '10k-50k',
            'niche' => 'travel',
            'country' => 'tajikistan',
            'about' => 'Settings driven',
            'status' => 'pending',
        ]);

        $result = app(PartnerApplicationService::class)->approve($application, 'SETTING10');

        $this->assertSame(350, $result['promo']->bonus_mb);
        $this->assertSame('2.25', (string) $result['promo']->partner_reward);
    }

    public function test_assign_to_partner_uses_program_settings_when_amounts_omitted(): void
    {
        $this->putSetting('user_bonus', '400');
        $this->putSetting('registration_reward', '3.00');

        $partner = Partner::query()->create([
            'name' => 'Assign Partner',
            'email' => 'assign-settings@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $promo = app(PromoCodeService::class)->assignToPartner($partner, 'ASSIGN99');

        $this->assertSame(400, $promo->bonus_mb);
        $this->assertSame('3.00', (string) $promo->partner_reward);
    }

    public function test_admin_promo_form_prefills_setting_defaults(): void
    {
        $this->putSetting('user_bonus', '275');
        $this->putSetting('registration_reward', '1.75');

        $partner = Partner::query()->create([
            'name' => 'Form Partner',
            'email' => 'form-settings@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        Livewire::test(AdminPromoCodes::class)
            ->call('openCreateModal', $partner->id)
            ->assertSet('formBonusType', 'mb')
            ->assertSet('formBonusAmount', '275')
            ->assertSet('formPartnerReward', '1.75');
    }

    public function test_apply_page_shows_setting_rates(): void
    {
        $this->putSetting('user_bonus', '180');
        $this->putSetting('registration_reward', '2.00');
        $this->putSetting('purchase_commission', '12');

        Livewire::test(ApplyForm::class)
            ->assertSee('$2.00')
            ->assertSee('12%')
            ->assertSee('180 MB');
    }

    public function test_existing_promo_amounts_stay_authoritative_on_redemption(): void
    {
        $this->putSetting('user_bonus', '999');
        $this->putSetting('registration_reward', '9.99');

        $partner = Partner::query()->create([
            'name' => 'Existing Promo',
            'email' => 'existing-promo@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'KEEPOLD1',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->assertDatabaseHas('promo_codes', [
            'code' => 'KEEPOLD1',
            'bonus_mb' => 200,
            'partner_reward' => '1.50',
        ]);
    }

    protected function putSetting(string $key, string $value): void
    {
        ProgramSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'label' => ['en' => $key],
                'description' => ['en' => $key],
                'sort_order' => 1,
            ],
        );
    }
}
