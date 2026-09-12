<?php

namespace Tests\Feature;

use App\Models\AuthActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $this->putJson('/api/user/profile', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'phone' => '+15550001',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => __('api.unauthenticated'),
                'data' => null,
            ]);
    }

    public function test_authenticated_user_can_update_profile_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+15551212',
            'bonus_mb' => 150,
            'balance' => '8.25',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => 'Ada Updated',
            'email' => 'ada.updated@example.com',
            'phone' => '+15559999',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('api.user.profile_updated'),
                'errors' => null,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Ada Updated',
                    'email' => 'ada.updated@example.com',
                    'phone' => '+15559999',
                    'balance' => '8.25',
                    'bonus_mb' => 150,
                ],
            ])
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Ada Updated',
            'email' => 'ada.updated@example.com',
            'phone' => '+15559999',
            'bonus_mb' => 150,
            'balance' => '8.25',
        ]);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PROFILE_UPDATED,
            'authenticatable_id' => $user->id,
        ]);
    }

    public function test_user_can_keep_the_same_email_and_phone(): void
    {
        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+15551212',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+15551212',
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'ada@example.com')
            ->assertJsonPath('data.phone', '+15551212');
    }

    public function test_update_rejects_duplicate_email_and_phone(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'phone' => '+15551212',
        ]);
        User::factory()->create([
            'email' => 'taken@example.com',
            'phone' => '+15550000',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => 'Ada',
            'email' => 'taken@example.com',
            'phone' => '+15550000',
        ])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'data' => null,
            ])
            ->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'current_password' => 'password123',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('api.user.profile_updated'));

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'current_password' => 'wrong-password',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_update_does_not_accept_wallet_or_bonus_fields(): void
    {
        $user = User::factory()->create([
            'balance' => '5.00',
            'bonus_mb' => 80,
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'balance' => '999.00',
            'bonus_mb' => 9999,
        ])
            ->assertOk()
            ->assertJsonPath('data.balance', '5.00')
            ->assertJsonPath('data.bonus_mb', 80);

        $this->assertSame('5.00', (string) $user->fresh()->balance);
        $this->assertSame(80, $user->fresh()->bonus_mb);
    }
}
