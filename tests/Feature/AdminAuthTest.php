<?php

namespace Tests\Feature;

use App\Livewire\Auth\AdminLogin;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\AuthSession;
use App\Services\Auth\AuthActivityLogger;
use App\Services\Auth\AuthSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_public(): void
    {
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->get(route('admin.applications'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_login_and_creates_session_and_log(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Test Admin',
            'email' => 'admin@zoodesim.test',
            'password' => 'password123',
        ]);

        Livewire::test(AdminLogin::class)
            ->set('email', 'admin@zoodesim.test')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_LOGIN_SUCCESS,
            'guard' => 'admin',
            'authenticatable_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('auth_sessions', [
            'authenticatable_id' => $admin->id,
            'guard' => 'admin',
        ]);
    }

    public function test_failed_login_is_logged(): void
    {
        Livewire::test(AdminLogin::class)
            ->set('email', 'nobody@example.com')
            ->set('password', 'wrongpass')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_LOGIN_FAILED,
            'email_attempted' => 'nobody@example.com',
            'guard' => 'admin',
        ]);
    }

    public function test_activity_logger_is_reusable_without_subject(): void
    {
        $log = app(AuthActivityLogger::class)->loginFailed('api', 'user@example.com', ['source' => 'future_api']);

        $this->assertSame(AuthActivityLog::EVENT_LOGIN_FAILED, $log->event);
        $this->assertSame('api', $log->guard);
        $this->assertSame(['source' => 'future_api'], $log->meta);
    }

    public function test_session_manager_can_terminate_other_sessions(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Test Admin',
            'email' => 'admin@zoodesim.test',
            'password' => 'password123',
        ]);

        Auth::guard('admin')->login($admin);

        $manager = app(AuthSessionManager::class);
        $current = $manager->start($admin, 'admin');

        $other = AuthSession::query()->create([
            'authenticatable_type' => $admin->getMorphClass(),
            'authenticatable_id' => $admin->id,
            'guard' => 'admin',
            'session_id' => 'other-session-id',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'OtherBot',
            'device_label' => 'OtherBot on Linux',
            'login_at' => now()->subHour(),
            'last_activity_at' => now()->subHour(),
        ]);

        $manager->terminateOthers($admin, 'admin');

        $this->assertNull($current->fresh()->revoked_at);
        $this->assertNotNull($other->fresh()->revoked_at);
    }
}
