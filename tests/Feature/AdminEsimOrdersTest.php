<?php

namespace Tests\Feature;

use App\Livewire\Admin\Orders;
use App\Models\Admin;
use App\Models\EsimOrder;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\CreatesEsimOrders;
use Tests\TestCase;

class AdminEsimOrdersTest extends TestCase
{
    use CreatesEsimOrders;
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_esim_orders(): void
    {
        $admin = $this->makeAdmin();
        $ada = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $bob = User::factory()->create([
            'name' => 'Bob Builder',
            'email' => 'bob@example.com',
        ]);

        $adaOrder = $this->makeEsimOrder($ada, [
            'resellportal_client_id' => 'cli-ada',
            'order_status' => EsimOrder::STATUS_ACTIVE,
        ]);
        $bobOrder = $this->makeEsimOrder($bob, [
            'resellportal_client_id' => 'cli-bob',
            'order_status' => EsimOrder::STATUS_FAILED,
            'package_code' => 'FAILED1',
        ]);

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.orders'))
            ->assertOk()
            ->assertSee($adaOrder->id)
            ->assertSee($bobOrder->id)
            ->assertSee('Ada Lovelace')
            ->assertSee('Bob Builder');

        Livewire::test(Orders::class)
            ->assertSee($adaOrder->id)
            ->assertSee($bobOrder->id)
            ->set('clientId', 'cli-ada')
            ->assertSee($adaOrder->id)
            ->assertDontSee($bobOrder->id)
            ->set('clientId', '')
            ->set('status', EsimOrder::STATUS_FAILED)
            ->assertSee($bobOrder->id)
            ->assertDontSee($adaOrder->id)
            ->set('status', '')
            ->set('user', 'ada@example.com')
            ->assertSee('Ada Lovelace')
            ->assertDontSee('Bob Builder');
    }

    public function test_live_tab_lists_resellportal_orders_and_forwards_client_id(): void
    {
        $admin = $this->makeAdmin();

        Http::fake([
            '*/orders*' => Http::response([
                'success' => true,
                'orders' => [[
                    'service_id' => 789,
                    'client_id' => 123,
                    'package_code' => 'PHAJHEAYP',
                    'package' => [
                        'name' => 'United States 1GB 7Days',
                        'location' => 'US',
                    ],
                    'amount_charged' => 1.80,
                    'esim_status' => 'active',
                ]],
            ], 200),
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(Orders::class)
            ->set('source', 'live')
            ->set('clientId', '123')
            ->assertSee('PHAJHEAYP')
            ->assertSee('United States 1GB 7Days')
            ->assertSee('123')
            ->assertDontSee('Name, email, or phone');

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains(strtok($request->url(), '?') ?: $request->url(), '/orders')
                && str_contains($request->url(), 'client_id=123');
        });
    }

    public function test_live_tab_shows_unavailable_when_provider_fails(): void
    {
        $admin = $this->makeAdmin();

        Http::fake([
            '*/orders*' => Http::response('Internal Server Error', 500),
        ]);

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.orders', ['source' => 'live']))
            ->assertOk()
            ->assertSee(__('admin.orders.live_unavailable'));
    }

    public function test_guest_cannot_open_orders(): void
    {
        $this->get(route('admin.orders'))->assertRedirect(route('admin.login'));
    }

    public function test_partner_cannot_open_orders(): void
    {
        $this->actingAs($this->makePartner(), 'partner')
            ->get(route('admin.orders'))
            ->assertForbidden();
    }

    protected function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Order Admin',
            'email' => 'order-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }

    protected function makePartner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Order Partner',
            'email' => 'order-partner@zoodesim.test',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }
}
