<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\Promo\PromoRedemptionService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UserRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_without_referral_code(): void
    {
        $response = $this->postJson('/api/user/register', $this->validPayload());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => __('api.user.registered'),
                'data' => ['bonus_mb' => 0, 'token_type' => 'Bearer'],
                'errors' => null,
            ])
            ->assertJsonPath('data.user.email', 'user@example.com')
            ->assertJsonPath('data.user.phone', '+1234567890')
            ->assertJsonMissingPath('data.user.password');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'phone' => '+1234567890',
        ]);
        $this->assertDatabaseCount('promo_usage', 0);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($response->json('data.token'))
            ->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('data.email', 'user@example.com');
    }

    public function test_user_can_register_with_valid_referral_code(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner, bonusMb: 250, reward: 2.25);

        $response = $this->postJson('/api/user/register', $this->validPayload([
            'referral_code' => '  valid10  ',
        ]));

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => ['bonus_mb' => 250, 'token_type' => 'Bearer'],
                'errors' => null,
            ]);

        $this->assertNotEmpty($response->json('data.token'));

        $user = User::query()->where('email', 'user@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame($user->id, $response->json('data.user.id'));

        $this->assertDatabaseHas('promo_usage', [
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'promo_code_id' => $promo->id,
            'bonus_mb_given' => 250,
            'partner_reward' => 2.25,
        ]);

        $this->assertSame(1, $promo->fresh()->usage_count);
        $this->assertEquals(2.25, (float) $partner->fresh()->balance);
        $this->assertEquals(2.25, (float) $partner->fresh()->total_earned);
    }

    public function test_invalid_referral_code_is_rejected(): void
    {
        $response = $this->postJson('/api/user/register', $this->validPayload([
            'referral_code' => 'MISSING1',
        ]));

        $this->assertPromoRejected($response, __('api.promo.invalid'));
    }

    public function test_expired_promo_code_is_rejected(): void
    {
        $this->makePromo($this->makePartner(), code: 'EXPIRED1', expiresAt: now()->subDay());

        $response = $this->postJson('/api/user/register', $this->validPayload([
            'referral_code' => 'EXPIRED1',
        ]));

        $this->assertPromoRejected($response, __('api.promo.expired'));
    }

    public function test_inactive_promo_code_is_rejected(): void
    {
        $this->makePromo($this->makePartner(), code: 'INACTIVE1', isActive: false);

        $response = $this->postJson('/api/user/register', $this->validPayload([
            'referral_code' => 'INACTIVE1',
        ]));

        $this->assertPromoRejected($response, __('api.promo.inactive'));
    }

    public function test_exhausted_promo_code_is_rejected(): void
    {
        $this->makePromo($this->makePartner(), code: 'LIMITED1', usageCount: 5, maxUsage: 5);

        $response = $this->postJson('/api/user/register', $this->validPayload([
            'referral_code' => 'LIMITED1',
        ]));

        $this->assertPromoRejected($response, __('api.promo.exhausted'));
    }

    public function test_promo_from_inactive_partner_is_rejected(): void
    {
        $partner = $this->makePartner(status: 'blocked');
        $this->makePromo($partner, code: 'BLOCKED1');

        $response = $this->postJson('/api/user/register', $this->validPayload([
            'referral_code' => 'BLOCKED1',
        ]));

        $this->assertPromoRejected($response, __('api.promo.partner_inactive'));
    }

    public function test_partner_cannot_use_own_promo_code(): void
    {
        $partner = $this->makePartner(email: 'partner@example.com');
        $this->makePromo($partner, code: 'OWNCODE1');

        $response = $this->postJson('/api/user/register', $this->validPayload([
            'email' => 'partner@example.com',
            'referral_code' => 'OWNCODE1',
        ]));

        $this->assertPromoRejected($response, __('api.promo.self_referral'));
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/user/register', $this->validPayload([
            'phone' => '+19998887777',
        ]));

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'data' => null,
            ]);

        $this->assertNotEmpty($response->json('errors.email'));

        $this->assertSame(1, User::query()->where('email', 'user@example.com')->count());
    }

    public function test_missing_phone_is_rejected(): void
    {
        $payload = $this->validPayload();
        unset($payload['phone']);

        $response = $this->postJson('/api/user/register', $payload);

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => __('api.validation.phone_required'),
                'data' => null,
            ]);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        User::factory()->create(['phone' => '+1234567890']);

        $response = $this->postJson('/api/user/register', $this->validPayload([
            'email' => 'other@example.com',
        ]));

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => __('api.validation.phone_unique'),
                'data' => null,
            ]);
    }

    public function test_registration_rolls_back_when_redemption_fails(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner, code: 'ROLLBACK1');

        $this->mock(PromoRedemptionService::class, function ($mock) {
            $mock->shouldReceive('redeem')
                ->once()
                ->andThrow(new \RuntimeException('forced failure'));
        });

        try {
            $this->withoutExceptionHandling();
            $this->postJson('/api/user/register', $this->validPayload([
                'referral_code' => 'ROLLBACK1',
            ]));
            $this->fail('Expected redemption failure to abort registration.');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced failure', $e->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'user@example.com']);
        $this->assertDatabaseCount('promo_usage', 0);
        $this->assertSame(0, $promo->fresh()->usage_count);
        $this->assertEquals(0, (float) $partner->fresh()->balance);
        $this->assertEquals(0, (float) $partner->fresh()->total_earned);
    }

    public function test_registration_messages_follow_request_locale(): void
    {
        $response = $this->postJson('/api/user/register', $this->validPayload([
            'referral_code' => 'MISSING1',
        ]), [
            'Accept-Language' => 'ru',
        ]);

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => trans('api.promo.invalid', [], 'ru'),
                'data' => null,
            ]);
    }

    public function test_locale_query_parameter_overrides_accept_language(): void
    {
        $response = $this->postJson('/api/user/register?lang=de', $this->validPayload([
            'referral_code' => 'MISSING1',
        ]), [
            'Accept-Language' => 'ru',
        ]);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => trans('api.promo.invalid', [], 'de'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'user@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    protected function makePartner(string $email = 'partner@example.com', string $status = 'active'): Partner
    {
        return Partner::query()->create([
            'name' => 'Referral Partner',
            'email' => $email,
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => $status,
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }

    protected function makePromo(
        Partner $partner,
        string $code = 'VALID10',
        int $bonusMb = 200,
        float $reward = 1.50,
        bool $isActive = true,
        ?CarbonInterface $expiresAt = null,
        int $usageCount = 0,
        ?int $maxUsage = null,
    ): PromoCode {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => $code,
            'bonus_mb' => $bonusMb,
            'partner_reward' => $reward,
            'type' => 'standard',
            'expires_at' => $expiresAt ?? now()->addDays(30),
            'is_active' => $isActive,
            'usage_count' => $usageCount,
            'max_usage' => $maxUsage,
        ]);
    }

    protected function assertPromoRejected(TestResponse $response, string $message): void
    {
        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => $message,
                'data' => null,
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'user@example.com']);
        $this->assertDatabaseCount('promo_usage', 0);
    }
}
