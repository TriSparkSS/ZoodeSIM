<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoValidateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_promo_returns_bonus_and_partner_name(): void
    {
        $this->makePromo($this->makePartner(), bonusMb: 250);

        $this->postJson('/api/promo/validate', ['code' => '  valid10  '])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('api.promo.valid'),
                'data' => [
                    'valid' => true,
                    'bonus_type' => 'mb',
                    'bonus_amount' => 250,
                    'bonus_mb' => 250,
                    'partner_name' => 'Referral Partner',
                    'reason' => null,
                ],
            ]);
    }

    public function test_invalid_promo_returns_valid_false(): void
    {
        $this->postJson('/api/promo/validate', ['code' => 'MISSING1'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'valid' => false,
                    'bonus_type' => null,
                    'bonus_amount' => 0,
                    'bonus_mb' => 0,
                    'partner_name' => null,
                    'reason' => __('api.promo.invalid'),
                ],
            ]);
    }

    public function test_expired_promo_is_reported_without_applying(): void
    {
        $this->makePromo($this->makePartner(), expiresAt: now()->subDay());

        $this->postJson('/api/promo/validate', ['code' => 'VALID10'])
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.reason', __('api.promo.expired'));

        $this->assertDatabaseCount('promo_usage', 0);
    }

    public function test_valid_usd_promo_returns_reward_dollar_bonus(): void
    {
        $this->makePromo($this->makePartner(), bonusMb: 0, expiresAt: null, bonusType: PromoCode::BONUS_TYPE_USD, bonusAmount: 5.00);

        $this->postJson('/api/promo/validate', ['code' => 'VALID10'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'bonus_type' => PromoCode::BONUS_TYPE_USD,
                    'bonus_amount' => 5,
                    'bonus_mb' => 0,
                    'partner_name' => 'Referral Partner',
                    'reason' => null,
                ],
            ]);
    }

    public function test_self_referral_email_is_rejected_on_validate(): void
    {
        $partner = $this->makePartner();
        $this->makePromo($partner);

        $this->postJson('/api/promo/validate', [
            'code' => 'VALID10',
            'email' => $partner->email,
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.reason', __('api.promo.self_referral'));
    }

    protected function makePartner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Referral Partner',
            'email' => 'partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }

    protected function makePromo(
        Partner $partner,
        int $bonusMb = 200,
        mixed $expiresAt = null,
        string $bonusType = PromoCode::BONUS_TYPE_MB,
        ?float $bonusAmount = null,
    ): PromoCode {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'VALID10',
            'bonus_mb' => $bonusMb,
            'bonus_type' => $bonusType,
            'bonus_amount' => $bonusAmount ?? $bonusMb,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => $expiresAt ?? now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);
    }
}
