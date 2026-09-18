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
