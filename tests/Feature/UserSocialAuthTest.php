<?php

namespace Tests\Feature;

use App\DataTransferObjects\FirebaseIdentity;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\Auth\Contracts\FirebaseTokenVerifierInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeFirebaseTokenVerifier;
use Tests\TestCase;

class UserSocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected FakeFirebaseTokenVerifier $tokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokens = new FakeFirebaseTokenVerifier;
        $this->app->instance(FirebaseTokenVerifierInterface::class, $this->tokens);
        $this->fakeResellPortalClientCreate();
    }

    public function test_new_google_user_is_registered(): void
    {
        $this->tokens->identity = $this->identity(
            uid: 'google-uid-1',
            email: 'google.user@example.com',
            name: 'Google User',
            provider: 'google.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-google-token',
            'provider' => 'google',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('api.user.registered'))
            ->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'google.user@example.com')
            ->assertJsonPath('data.user.phone', null)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.firebase_uid');

        $user = User::query()->where('email', 'google.user@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-uid-1', $user->firebase_uid);
        $this->assertSame(User::AUTH_GOOGLE, $user->auth_provider);
        $this->assertNull($user->phone);
        $this->assertNull($user->password);
    }

    public function test_returning_apple_user_logs_in(): void
    {
        $user = User::factory()->social(User::AUTH_APPLE)->create([
            'email' => 'apple.user@example.com',
            'firebase_uid' => 'apple-uid-1',
            'name' => 'Apple User',
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'apple-uid-1',
            email: 'apple.user@example.com',
            name: 'Apple User',
            provider: 'apple.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-apple-token',
            'provider' => 'apple',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('api.user.logged_in'))
            ->assertJsonPath('data.is_new_user', false)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_email_account_is_linked(): void
    {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'password' => 'password123',
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'google-link-1',
            email: 'linked@example.com',
            provider: 'google.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'google',
        ])
            ->assertOk()
            ->assertJsonPath('data.is_new_user', false)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertSame('google-link-1', $user->fresh()->firebase_uid);
        $this->assertSame(User::AUTH_PASSWORD, $user->fresh()->auth_provider);
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->tokens->fail = true;

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'bad-token',
            'provider' => 'google',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('api.user.social_invalid'));
    }

    public function test_provider_mismatch_is_rejected(): void
    {
        $this->tokens->identity = $this->identity(
            uid: 'google-uid-2',
            email: 'mismatch@example.com',
            provider: 'google.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'apple',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    public function test_missing_email_on_new_account_is_rejected(): void
    {
        $this->tokens->identity = $this->identity(
            uid: 'apple-no-email',
            email: null,
            provider: 'apple.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'apple',
            'name' => 'Hidden Email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_token']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_social_only_user_cannot_login_with_password(): void
    {
        User::factory()->social()->create([
            'email' => 'social-only@example.com',
        ]);

        $this->postJson('/api/user/login', [
            'email' => 'social-only@example.com',
            'password' => 'password123',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', __('auth.failed'));
    }

    public function test_social_user_can_add_phone_on_profile(): void
    {
        $user = User::factory()->social()->create([
            'email' => 'nophone@example.com',
            'phone' => null,
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+15550199',
        ])
            ->assertOk()
            ->assertJsonPath('data.phone', '+15550199');
    }

    public function test_new_google_user_can_apply_referral_code(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Social Partner',
            'email' => 'social-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'SOCIAL10',
            'bonus_mb' => 150,
            'bonus_type' => PromoCode::BONUS_TYPE_MB,
            'bonus_amount' => 150,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'google-ref-1',
            email: 'referred@example.com',
            name: 'Referred User',
            provider: 'google.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'google',
            'referral_code' => 'SOCIAL10',
            'device_id' => 'social-device-001',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.bonus_mb', 150);

        $this->assertDatabaseCount('user_referrals', 0);
        $this->assertDatabaseHas('promo_usage', [
            'user_id' => User::query()->where('email', 'referred@example.com')->value('id'),
        ]);
    }

    public function test_new_google_user_can_apply_another_users_referral_code(): void
    {
        $referrer = User::factory()->create([
            'email' => 'social-host@example.com',
            'phone' => '+15551111888',
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'google-user-ref-1',
            email: 'social-guest@example.com',
            name: 'Social Guest',
            provider: 'google.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'google',
            'referral_code' => $referrer->referral_code,
            'device_id' => 'social-user-device',
        ])
            ->assertCreated()
            ->assertJsonPath('data.bonus_type', PromoCode::BONUS_TYPE_USD)
            ->assertJsonPath('data.bonus_amount', 1)
            ->assertJsonPath('data.bonus_mb', 0);

        $this->assertDatabaseCount('promo_usage', 0);
        $this->assertDatabaseCount('user_referrals', 1);
        $this->assertEquals(1.50, (float) $referrer->fresh()->balance);
    }

    public function test_new_facebook_user_is_registered(): void
    {
        $this->tokens->identity = $this->identity(
            uid: 'facebook-uid-1',
            email: 'fb.user@example.com',
            name: 'Facebook User',
            provider: 'facebook.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-facebook-token',
            'provider' => 'facebook',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.user.email', 'fb.user@example.com');

        $user = User::query()->where('email', 'fb.user@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('facebook-uid-1', $user->firebase_uid);
        $this->assertSame(User::AUTH_FACEBOOK, $user->auth_provider);
        $this->assertNull($user->password);
    }

    public function test_returning_facebook_user_logs_in_without_creating_a_duplicate(): void
    {
        $user = User::factory()->social(User::AUTH_FACEBOOK)->create([
            'email' => 'fb.return@example.com',
            'firebase_uid' => 'facebook-uid-return',
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'facebook-uid-return',
            email: 'fb.return@example.com',
            provider: 'facebook.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-facebook-token',
            'provider' => 'facebook',
            'referral_code' => 'IGNORED1',
            'device_id' => 'fb-device-001',
        ])
            ->assertOk()
            ->assertJsonPath('data.is_new_user', false)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_referrals', 0);
    }

    public function test_firebase_password_provider_registers_like_social(): void
    {
        $this->tokens->identity = $this->identity(
            uid: 'password-uid-1',
            email: 'firebase.pass@example.com',
            name: 'Firebase Password',
            provider: 'password',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-password-token',
            'provider' => 'password',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_new_user', true);

        $user = User::query()->where('email', 'firebase.pass@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(User::AUTH_PASSWORD, $user->auth_provider);
        $this->assertSame('password-uid-1', $user->firebase_uid);
        $this->assertNull($user->password);
    }

    public function test_partner_email_cannot_create_a_social_user(): void
    {
        Partner::query()->create([
            'name' => 'Taken Partner',
            'email' => 'shared@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'facebook-taken-1',
            email: 'shared@example.com',
            provider: 'facebook.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'facebook',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_conflicting_firebase_uid_on_same_email_is_rejected(): void
    {
        User::factory()->social(User::AUTH_GOOGLE)->create([
            'email' => 'clash@example.com',
            'firebase_uid' => 'google-already',
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'facebook-other',
            email: 'clash@example.com',
            provider: 'facebook.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'facebook',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('google-already', User::query()->where('email', 'clash@example.com')->value('firebase_uid'));
    }

    public function test_facebook_user_can_apply_partner_promo(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Facebook Partner',
            'email' => 'fb-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'FBPROMO1',
            'bonus_mb' => 120,
            'bonus_type' => PromoCode::BONUS_TYPE_MB,
            'bonus_amount' => 120,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'facebook-promo-1',
            email: 'fb-promo-user@example.com',
            name: 'FB Promo',
            provider: 'facebook.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'facebook',
            'referral_code' => 'FBPROMO1',
            'device_id' => 'fb-promo-device',
        ])
            ->assertCreated()
            ->assertJsonPath('data.bonus_mb', 120);

        $this->assertDatabaseCount('user_referrals', 0);
        $this->assertDatabaseHas('promo_usage', [
            'user_id' => User::query()->where('email', 'fb-promo-user@example.com')->value('id'),
            'promo_code_id' => PromoCode::query()->where('code', 'FBPROMO1')->value('id'),
        ]);
        $this->assertEquals(1.50, (float) $partner->fresh()->balance);
    }

    public function test_facebook_user_can_apply_user_referral_code(): void
    {
        $referrer = User::factory()->create([
            'email' => 'fb-host@example.com',
            'phone' => '+15551111777',
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'facebook-user-ref-1',
            email: 'fb-guest@example.com',
            name: 'FB Guest',
            provider: 'facebook.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'facebook',
            'referral_code' => $referrer->referral_code,
            'device_id' => 'fb-user-ref-device',
        ])
            ->assertCreated()
            ->assertJsonPath('data.bonus_type', PromoCode::BONUS_TYPE_USD)
            ->assertJsonPath('data.bonus_amount', 1);

        $this->assertDatabaseCount('promo_usage', 0);
        $this->assertDatabaseCount('user_referrals', 1);
        $this->assertEquals(1.50, (float) $referrer->fresh()->balance);
        $this->assertEquals(1.00, (float) User::query()->where('email', 'fb-guest@example.com')->value('balance'));
    }

    public function test_firebase_password_user_can_apply_user_referral_code(): void
    {
        $referrer = User::factory()->create([
            'email' => 'pass-host@example.com',
            'phone' => '+15551111666',
        ]);

        $this->tokens->identity = $this->identity(
            uid: 'password-user-ref-1',
            email: 'pass-guest@example.com',
            name: 'Pass Guest',
            provider: 'password',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'password',
            'referral_code' => $referrer->referral_code,
            'device_id' => 'pass-user-ref-device',
        ])
            ->assertCreated()
            ->assertJsonPath('data.bonus_type', PromoCode::BONUS_TYPE_USD);

        $this->assertDatabaseCount('user_referrals', 1);
        $this->assertEquals(1.50, (float) $referrer->fresh()->balance);
    }

    protected function identity(
        string $uid,
        ?string $email,
        string $provider,
        ?string $name = null,
        bool $verified = true,
    ): FirebaseIdentity {
        return new FirebaseIdentity(
            uid: $uid,
            email: $email,
            name: $name,
            emailVerified: $verified,
            signInProvider: $provider,
        );
    }
}
