<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserReferrals;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\Referral\Contracts\UserReferralServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class UserReferralApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeResellPortalClientCreate();
    }

    public function test_registering_with_a_user_code_credits_both_usd_wallets(): void
    {
        $referrer = User::factory()->create([
            'name' => 'Referrer User',
            'email' => 'referrer@example.com',
            'phone' => '+15551111001',
        ]);

        $response = $this->postJson('/api/user/register', $this->payload([
            'referral_code' => $referrer->referral_code,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.bonus_type', PromoCode::BONUS_TYPE_USD)
            ->assertJsonPath('data.bonus_amount', 1)
            ->assertJsonPath('data.bonus_mb', 0)
            ->assertJsonPath('data.user.balance', '1.00');

        $this->assertNotEmpty($response->json('data.user.referral_code'));
        $this->assertNotSame($referrer->referral_code, $response->json('data.user.referral_code'));

        $invitee = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->assertSame($referrer->id, $invitee->referred_by_user_id);
        $this->assertEquals(1.50, (float) $referrer->fresh()->balance);
        $this->assertEquals(1.00, (float) $invitee->balance);
        $this->assertDatabaseCount('promo_usage', 0);
        $this->assertDatabaseHas('user_referrals', [
            'referrer_id' => $referrer->id,
            'referred_id' => $invitee->id,
            'referrer_amount' => '1.50',
            'referred_amount' => '1.00',
            'currency' => 'USD',
        ]);
        $this->assertDatabaseHas('transactions', [
            'transactable_type' => User::class,
            'transactable_id' => $invitee->id,
            'category' => Transaction::CATEGORY_USER_REFERRAL_BONUS,
            'amount' => '1.00',
            'currency' => 'USD',
        ]);
        $this->assertDatabaseHas('transactions', [
            'transactable_type' => User::class,
            'transactable_id' => $referrer->id,
            'category' => Transaction::CATEGORY_USER_REFERRAL_REWARD,
            'amount' => '1.50',
            'currency' => 'USD',
        ]);
    }

    public function test_self_referral_with_user_code_is_rejected(): void
    {
        $referrer = User::factory()->create([
            'email' => 'host@example.com',
            'phone' => '+15551111002',
        ]);

        $this->postJson('/api/promo/validate', [
            'code' => $referrer->referral_code,
            'email' => $referrer->email,
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.reason', __('api.promo.self_referral'));

        try {
            app(UserReferralServiceInterface::class)
                ->redeem($referrer, $referrer);
            $this->fail('Expected self-referral to be rejected.');
        } catch (ValidationException $e) {
            $this->assertSame(__('api.promo.self_referral'), collect($e->errors())->flatten()->first());
        }

        $this->assertDatabaseCount('user_referrals', 0);
    }

    public function test_partner_promo_register_does_not_create_user_referrals(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);

        $this->postJson('/api/user/register', $this->payload([
            'referral_code' => $promo->code,
        ]))->assertCreated();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->assertDatabaseHas('promo_usage', [
            'user_id' => $user->id,
            'promo_code_id' => $promo->id,
            'partner_id' => $partner->id,
        ]);
        $this->assertEquals(1.50, (float) $partner->fresh()->balance);
        $this->assertDatabaseCount('user_referrals', 0);
        $this->assertNull($user->referred_by_user_id);
        $this->assertDatabaseMissing('transactions', [
            'category' => Transaction::CATEGORY_USER_REFERRAL_BONUS,
        ]);
        $this->assertDatabaseMissing('transactions', [
            'category' => Transaction::CATEGORY_USER_REFERRAL_REWARD,
        ]);
    }

    public function test_same_device_cannot_redeem_a_second_user_referral(): void
    {
        $firstReferrer = User::factory()->create(['phone' => '+15551111003']);
        $secondReferrer = User::factory()->create(['phone' => '+15551111004']);

        $this->postJson('/api/user/register', $this->payload([
            'referral_code' => $firstReferrer->referral_code,
            'device_id' => 'shared-user-phone',
        ]))->assertCreated();

        $this->postJson('/api/user/register', $this->payload([
            'name' => 'Second Invitee',
            'email' => 'second@example.com',
            'phone' => '+15550000002',
            'referral_code' => $secondReferrer->referral_code,
            'device_id' => 'shared-user-phone',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.promo.device_already_used'));

        $this->assertDatabaseCount('user_referrals', 1);
        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
    }

    public function test_device_used_for_partner_promo_can_still_use_a_user_code(): void
    {
        $partner = $this->makePartner();
        $this->makePromo($partner);

        $this->postJson('/api/user/register', $this->payload([
            'referral_code' => 'VALID10',
            'device_id' => 'mixed-device-01',
        ]))->assertCreated();

        $referrer = User::factory()->create(['phone' => '+15551111005']);

        $this->postJson('/api/user/register', $this->payload([
            'email' => 'user-ref@example.com',
            'phone' => '+15550000009',
            'referral_code' => $referrer->referral_code,
            'device_id' => 'mixed-device-01',
        ]))->assertCreated();

        $this->assertDatabaseCount('promo_usage', 1);
        $this->assertDatabaseCount('user_referrals', 1);
    }

    public function test_user_referral_list_api_returns_safe_fields(): void
    {
        $referrer = User::factory()->create([
            'name' => 'Host User',
            'email' => 'host@example.com',
            'phone' => '+15551111006',
        ]);
        $invitee = User::factory()->create([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'phone' => '+15551111007',
            'referred_by_user_id' => $referrer->id,
        ]);

        UserReferral::query()->create([
            'referrer_id' => $referrer->id,
            'referred_id' => $invitee->id,
            'referrer_amount' => '1.50',
            'referred_amount' => '1.00',
            'currency' => 'USD',
        ]);

        Sanctum::actingAs($referrer);

        $this->getJson('/api/user/referral')
            ->assertOk()
            ->assertJsonPath('message', __('api.user.referral'))
            ->assertJsonPath('data.referral_code', $referrer->referral_code)
            ->assertJsonPath('data.referrer_reward', 1.5)
            ->assertJsonPath('data.referred_reward', 1)
            ->assertJsonPath('data.referral_count', 1)
            ->assertJsonPath('data.total_earned', 1.5)
            ->assertJsonPath('data.referrals.0.id', $invitee->id)
            ->assertJsonPath('data.referrals.0.name', 'Guest User')
            ->assertJsonMissingPath('data.referrals.0.email')
            ->assertJsonMissingPath('data.referrals.0.phone');
    }

    public function test_profile_includes_referral_code(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('data.referral_code', $user->referral_code);
    }

    public function test_validate_user_code_returns_invitee_usd_preview(): void
    {
        $referrer = User::factory()->create(['phone' => '+15551111008']);

        $this->postJson('/api/promo/validate', ['code' => '  '.$referrer->referral_code.'  '])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('api.promo.valid'),
                'data' => [
                    'valid' => true,
                    'bonus_type' => PromoCode::BONUS_TYPE_USD,
                    'bonus_amount' => 1,
                    'bonus_mb' => 0,
                    'partner_name' => null,
                    'reason' => null,
                ],
            ]);
    }

    public function test_validate_partner_code_payload_is_unchanged(): void
    {
        $this->makePromo($this->makePartner(), bonusMb: 250);

        $this->postJson('/api/promo/validate', ['code' => '  valid10  '])
            ->assertOk()
            ->assertJson([
                'success' => true,
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

    public function test_admin_user_referral_page_lists_rows(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Referral Admin',
            'email' => 'referral-admin@zoodesim.test',
            'password' => 'password123',
        ]);
        $referrer = User::factory()->create([
            'name' => 'Alice Referrer',
            'email' => 'alice@example.com',
            'phone' => '+15551111009',
        ]);
        $invitee = User::factory()->create([
            'name' => 'Bob Invitee',
            'email' => 'bob@example.com',
            'phone' => '+15551111010',
            'referred_by_user_id' => $referrer->id,
        ]);
        UserReferral::query()->create([
            'referrer_id' => $referrer->id,
            'referred_id' => $invitee->id,
            'referrer_amount' => '1.50',
            'referred_amount' => '1.00',
            'currency' => 'USD',
        ]);

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.user-referrals'))
            ->assertOk()
            ->assertSee('Alice Referrer')
            ->assertSee('Bob Invitee');

        Livewire::test(UserReferrals::class)
            ->assertSee('Alice Referrer')
            ->set('search', 'nomatch')
            ->assertDontSee('Alice Referrer');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'user@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_id' => 'test-device-001',
        ], $overrides);
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

    protected function makePromo(Partner $partner, int $bonusMb = 200): PromoCode
    {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'VALID10',
            'bonus_mb' => $bonusMb,
            'bonus_type' => PromoCode::BONUS_TYPE_MB,
            'bonus_amount' => $bonusMb,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);
    }
}
