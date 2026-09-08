<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+15550001111',
        ]);

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertSee('ada@example.com');

        Livewire::test(Users::class)
            ->assertSee($user->email)
            ->set('search', 'nomatch')
            ->assertDontSee($user->email);
    }

    public function test_admin_can_update_user_details(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '+15550002222',
            'password' => 'keep-this-password',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(Users::class)
            ->call('openEdit', $user->id)
            ->set('editName', 'New Name')
            ->set('editEmail', 'new@example.com')
            ->set('editPhone', '+15550003333')
            ->set('editPassword', '')
            ->call('saveEdit')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('+15550003333', $user->phone);
        $this->assertTrue(Hash::check('keep-this-password', $user->password));

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_USER_UPDATED,
            'guard' => 'admin',
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_can_update_user_password(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create([
            'password' => 'old-password',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(Users::class)
            ->call('openEdit', $user->id)
            ->set('editPassword', 'new-password123')
            ->set('editPasswordConfirmation', 'new-password123')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_partner_cannot_access_users_page(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Partner One',
            'email' => 'partner-users@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $this->actingAs($partner, 'partner')
            ->get(route('admin.users'))
            ->assertForbidden();
    }

    protected function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Users Admin',
            'email' => 'users-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }
}
