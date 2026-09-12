<?php

namespace Tests\Feature;

use App\DataTransferObjects\CreatePromoCodeData;
use App\Livewire\Partner\PromoCodes as PartnerPromoCodes;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use App\Services\Partner\PartnerPortalDataService;
use App\Services\Promo\PromoCodeService;
use App\Services\Promo\PromoRedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromoLadderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeResellPortalClientCreate();
    }

    public function test_ninth_redemption_keeps_next_code_locked(): void
    {
        [$partner, $promo1, $promo2, $promo3] = $this->seedLadder();

        $this->redeemTimes($promo1, 9);

        $this->assertTrue($promo1->fresh()->isCurrentlyUsable());
        $this->assertTrue($promo2->fresh()->isLocked());
        $this->assertFalse($promo2->fresh()->is_active);
        $this->assertTrue($promo3->fresh()->isLocked());
        $this->assertSame(9, PromoUsage::query()->where('partner_id', $partner->id)->count());
    }

    public function test_tenth_redemption_activates_next_code_and_deactivates_previous(): void
    {
        [$partner, $promo1, $promo2, $promo3] = $this->seedLadder();

        $this->redeemTimes($promo1, 10);

        $this->assertFalse($promo1->fresh()->is_active);
        $this->assertFalse($promo1->fresh()->isLocked());
        $this->assertTrue($promo2->fresh()->isCurrentlyUsable());
        $this->assertNotNull($promo2->fresh()->unlocked_at);
        $this->assertTrue($promo3->fresh()->isLocked());
        $this->assertSame(10, PromoUsage::query()->where('partner_id', $partner->id)->count());
    }

    public function test_after_unlock_validate_and_register_require_the_active_code(): void
    {
        [$partner, $promo1, $promo2, $promo3] = $this->seedLadder();

        $this->redeemTimes($promo1, 10);

        $this->postJson('/api/promo/validate', ['code' => $promo1->code])
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.reason', __('api.promo.inactive'));

        $this->postJson('/api/promo/validate', ['code' => $promo2->code])
            ->assertOk()
            ->assertJsonPath('data.valid', true);

        $this->postJson('/api/promo/validate', ['code' => $promo3->code])
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.reason', __('api.promo.locked'));

        $this->postJson('/api/user/register', $this->registerPayload([
            'email' => 'next-user@example.com',
            'phone' => '+15550000011',
            'referral_code' => $promo1->code,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.promo.inactive'));

        $this->postJson('/api/user/register', $this->registerPayload([
            'email' => 'locked-user@example.com',
            'phone' => '+15550000012',
            'referral_code' => $promo3->code,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.promo.locked'));

        $this->postJson('/api/user/register', $this->registerPayload([
            'email' => 'active-user@example.com',
            'phone' => '+15550000013',
            'referral_code' => $promo2->code,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'active-user@example.com');

        $this->assertTrue($promo2->fresh()->isCurrentlyUsable());
        $this->assertSame(11, PromoUsage::query()->where('partner_id', $partner->id)->count());
    }

    public function test_partner_list_masks_locked_code_and_shows_progress(): void
    {
        $partner = $this->makePartner();
        $this->makePromo($partner, 'LIVECODE', unlockRequirement: null, isActive: true);

        $locked = $this->makePromo($partner, 'SECRET10', unlockRequirement: 10, isActive: false);

        for ($i = 0; $i < 7; $i++) {
            PromoUsage::query()->create([
                'promo_code_id' => $locked->id,
                'user_id' => User::factory()->create()->id,
                'partner_id' => $partner->id,
                'bonus_mb_given' => 200,
                'partner_reward' => 1.50,
                'used_at' => now(),
            ]);
        }

        $this->actingAs($partner, 'partner');

        $stats = app(PartnerPortalDataService::class)->stats($partner);
        $this->assertSame('LIVECODE', $stats['promo_code']);

        Livewire::test(PartnerPromoCodes::class)
            ->assertSee('LIVECODE')
            ->assertSee('••••••••')
            ->assertDontSee('SECRET10')
            ->assertSee('7/10')
            ->assertSee(__('partner.promo_codes.status_locked'));
    }

    /**
     * @return array{0: Partner, 1: PromoCode, 2: PromoCode, 3: PromoCode}
     */
    protected function seedLadder(): array
    {
        $partner = $this->makePartner();
        $service = app(PromoCodeService::class);

        $promo1 = $service->create(new CreatePromoCodeData(
            partnerId: $partner->id,
            code: 'PROMOONE1',
            bonusMb: 200,
            partnerReward: 1.50,
            expiresAt: now()->addDays(30),
            isActive: true,
        ));

        $promo2 = $service->create(new CreatePromoCodeData(
            partnerId: $partner->id,
            code: 'PROMOTEN2',
            bonusMb: 250,
            partnerReward: 2.00,
            expiresAt: now()->addDays(30),
            isActive: false,
            deactivateExistingActive: true,
            unlockRequirement: 10,
        ));

        $promo3 = $service->create(new CreatePromoCodeData(
            partnerId: $partner->id,
            code: 'PROMO20X3',
            bonusMb: 300,
            partnerReward: 2.50,
            expiresAt: now()->addDays(30),
            isActive: false,
            deactivateExistingActive: true,
            unlockRequirement: 20,
        ));

        $this->assertTrue($promo1->fresh()->isCurrentlyUsable());
        $this->assertTrue($promo2->fresh()->isLocked());
        $this->assertTrue($promo3->fresh()->isLocked());

        return [$partner, $promo1, $promo2, $promo3];
    }

    protected function redeemTimes(PromoCode $promo, int $times): void
    {
        $redemption = app(PromoRedemptionService::class);

        for ($i = 0; $i < $times; $i++) {
            $redemption->redeem(User::factory()->create(), $promo->fresh());
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'user@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_id' => 'test-device-'.uniqid(),
        ], $overrides);
    }

    protected function makePartner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Ladder Partner',
            'email' => 'ladder-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }

    protected function makePromo(
        Partner $partner,
        string $code,
        ?int $unlockRequirement,
        bool $isActive,
    ): PromoCode {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => $code,
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => $isActive,
            'usage_count' => 0,
            'max_usage' => null,
            'unlock_requirement' => $unlockRequirement,
            'unlocked_at' => $unlockRequirement === null ? now() : null,
        ]);
    }
}
