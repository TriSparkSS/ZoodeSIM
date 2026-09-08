<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserLoginApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('api.user.logged_in'),
                'errors' => null,
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'user@example.com')
            ->assertJsonPath('data.user.phone', $user->phone)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token');

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => __('auth.failed'),
                'data' => null,
            ]);
    }

    public function test_login_is_throttled_after_too_many_attempts(): void
    {
        User::factory()->create([
            'email' => 'throttled@example.com',
            'password' => 'password123',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/user/login', [
                'email' => 'throttled@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/user/login', [
            'email' => 'throttled@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429)
            ->assertJson([
                'success' => false,
                'data' => null,
            ]);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/user/profile')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => __('api.unauthenticated'),
                'data' => null,
            ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/user/logout')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => __('api.unauthenticated'),
                'data' => null,
            ]);
    }

    public function test_authenticated_user_can_view_own_profile(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Referral Partner',
            'email' => 'partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $promo = PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'PROF10',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->postJson('/api/user/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+15551212',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'referral_code' => $promo->code,
            'device_id' => 'login-device-001',
        ])->assertCreated();

        $login = $this->postJson('/api/user/login', [
            'email' => 'ada@example.com',
            'password' => 'password123',
        ])->assertOk();

        $token = $login->json('data.token');
        $user = User::query()->where('email', 'ada@example.com')->first();

        $other = User::factory()->create();

        $this->withToken($token)
            ->getJson('/api/user/profile')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('api.user.profile'),
                'errors' => null,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Ada Lovelace',
                    'email' => 'ada@example.com',
                    'phone' => '+15551212',
                    'bonus_mb' => 200,
                ],
            ])
            ->assertJsonMissing(['id' => $other->id])
            ->assertJsonMissingPath('data.password');
    }

    public function test_user_cannot_view_another_users_profile_via_token(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        Sanctum::actingAs($first);

        $this->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $first->id)
            ->assertJsonMissing(['email' => $second->email]);
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $token = $this->postJson('/api/user/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $this->withToken($token)
            ->postJson('/api/user/logout')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('api.user.logged_out'),
                'errors' => null,
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user/profile')
            ->assertUnauthorized();
    }

    public function test_response_envelope_is_consistent_for_login_validation_errors(): void
    {
        $this->postJson('/api/user/login', [])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'data' => null,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors' => ['email', 'password'],
            ]);
    }
}
