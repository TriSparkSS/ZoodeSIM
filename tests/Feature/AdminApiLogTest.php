<?php

namespace Tests\Feature;

use App\Livewire\Admin\ApiLogs;
use App\Livewire\Admin\ApiLogShow;
use App\Models\Admin;
use App\Models\ApiLog;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class AdminApiLogTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Log Admin',
            'email' => 'log-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }

    protected function partner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Log Partner',
            'email' => 'log-partner@zoodesim.test',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }

    public function test_admin_can_list_and_filter_logs(): void
    {
        $user = User::factory()->create(['email' => 'buyer@example.com']);

        ApiLog::factory()->create([
            'type' => ApiLog::TYPE_INTERNAL,
            'service' => ApiLog::SERVICE_PORTAL,
            'method' => 'GET',
            'endpoint' => '/api/user/profile',
            'response_status' => 200,
            'response_time_ms' => 20,
            'user_id' => $user->id,
        ]);
        ApiLog::factory()->create([
            'type' => ApiLog::TYPE_THIRD_PARTY,
            'service' => ApiLog::SERVICE_RESELLPORTAL,
            'method' => 'POST',
            'endpoint' => '/orders',
            'response_status' => 500,
            'response_time_ms' => 2500,
            'error_message' => 'HTTP 500',
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(ApiLogs::class)
            ->assertSee('/api/user/profile')
            ->assertSee('/orders')
            ->set('failed', true)
            ->assertSee('/orders')
            ->assertDontSee('/api/user/profile')
            ->set('failed', false)
            ->set('slow', true)
            ->assertSee('/orders')
            ->assertDontSee('/api/user/profile')
            ->set('slow', false)
            ->set('type', ApiLog::TYPE_INTERNAL)
            ->assertSee('/api/user/profile')
            ->assertDontSee('/orders')
            ->set('type', '')
            ->set('user', 'buyer@example.com')
            ->assertSee('/api/user/profile')
            ->assertDontSee('/orders');
    }

    public function test_admin_can_view_log_details_with_secrets_masked(): void
    {
        $log = ApiLog::factory()->create([
            'type' => ApiLog::TYPE_THIRD_PARTY,
            'service' => ApiLog::SERVICE_RESELLPORTAL,
            'method' => 'POST',
            'endpoint' => '/clients',
            'full_url' => 'https://panel.resellportal.com/wp-json/resellportal/v1/clients',
            'request_headers' => [
                'Authorization' => 'Bearer leaked-token',
                'X-API-Key' => 'test-api-key',
            ],
            'request_body' => [
                'email' => 'user@example.com',
                'password' => 'should-not-appear',
            ],
            'response_status' => 200,
            'response_body' => ['success' => true, 'api_secret' => 'hidden'],
            'error_message' => null,
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(ApiLogShow::class, ['log' => $log->id])
            ->assertSee('POST')
            ->assertSee('/clients')
            ->assertSee('user@example.com')
            ->assertSee('********')
            ->assertDontSee('leaked-token')
            ->assertDontSee('test-api-key')
            ->assertDontSee('should-not-appear');
    }

    public function test_guest_cannot_open_api_logs(): void
    {
        $this->get(route('admin.api-logs'))->assertRedirect(route('admin.login'));
    }

    public function test_partner_cannot_open_api_logs(): void
    {
        $this->actingAs($this->partner(), 'partner')
            ->get(route('admin.api-logs'))
            ->assertForbidden();
    }

    public function test_normal_user_cannot_open_api_logs(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->get(route('admin.api-logs'))->assertRedirect(route('admin.login'));
    }
}
