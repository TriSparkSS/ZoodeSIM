<?php

namespace Tests\Feature;

use App\DataTransferObjects\FirebaseIdentity;
use App\Livewire\Admin\Users;
use App\Models\Admin;
use App\Models\User;
use App\Services\Auth\Contracts\FirebaseTokenVerifierInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\Support\FakeFirebaseTokenVerifier;
use Tests\TestCase;

class UserAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_soft_delete_account_and_tokens_are_revoked(): void
    {
        $user = User::factory()->create([
            'email' => 'delete-me@example.com',
            'phone' => '+15550004444',
            'password' => 'password123',
        ]);

        $token = $user->createToken('user-api')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/user/account')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('api.user.account_deleted'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user/profile')
            ->assertUnauthorized();
    }

    public function test_deleted_user_cannot_login_or_reuse_email(): void
    {
        $this->fakeResellPortalClientCreate();

        $user = User::factory()->create([
            'email' => 'gone@example.com',
            'phone' => '+15550005555',
            'password' => 'password123',
        ]);
        $user->delete();

        $this->postJson('/api/user/login', [
            'email' => 'gone@example.com',
            'password' => 'password123',
        ])
            ->assertUnauthorized();

        $this->postJson('/api/user/register', [
            'name' => 'New Person',
            'email' => 'gone@example.com',
            'phone' => '+15550006666',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.validation.email_unique'));
    }

    public function test_deleted_social_user_cannot_sign_in_again(): void
    {
        $tokens = new FakeFirebaseTokenVerifier;
        $this->app->instance(FirebaseTokenVerifierInterface::class, $tokens);

        $user = User::factory()->social(User::AUTH_GOOGLE)->create([
            'email' => 'gone.google@example.com',
            'firebase_uid' => 'deleted-google-uid',
        ]);
        $user->delete();

        $tokens->identity = new FirebaseIdentity(
            uid: 'deleted-google-uid',
            email: 'gone.google@example.com',
            name: 'Gone Google',
            emailVerified: true,
            signInProvider: 'google.com',
        );

        $this->postJson('/api/user/auth/social', [
            'id_token' => 'fake-token',
            'provider' => 'google',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.user.account_deleted'));
    }

    public function test_admin_still_sees_deleted_users_as_view_only(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Deletion Admin',
            'email' => 'deletion-admin@zoodesim.test',
            'password' => 'password123',
        ]);

        $user = User::factory()->create([
            'name' => 'Soft Deleted User',
            'email' => 'soft-deleted@example.com',
        ]);
        $user->delete();

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Soft Deleted User')
            ->assertSee(__('admin.users.deleted'))
            ->assertSee(__('admin.users.deleted_view_only'));

        Livewire::test(Users::class)
            ->assertSee('soft-deleted@example.com')
            ->call('openEdit', $user->id)
            ->assertSet('showEditModal', false);
    }
}
