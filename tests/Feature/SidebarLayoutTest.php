<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SidebarLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_nav_is_scrollable_and_includes_all_items(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Sidebar Admin',
            'email' => 'sidebar-admin@zoodesim.test',
            'password' => 'password123',
        ]);

        Http::fake([
            '*/balance' => Http::response([
                'success' => true,
                'balance' => 10.00,
                'currency' => 'USD',
            ], 200),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('overflow-y-auto', false)
            ->assertSee('sidebar-nav', false)
            ->assertSee(__('admin.nav.banners'))
            ->assertSee(__('admin.nav.api_logs'))
            ->assertSee(__('admin.nav.settings'));
    }

    public function test_partner_sidebar_nav_is_scrollable(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Sidebar Partner',
            'email' => 'sidebar-partner@zoodesim.test',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $this->actingAs($partner, 'partner')
            ->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee('overflow-y-auto', false)
            ->assertSee('sidebar-nav', false)
            ->assertSee(__('partner.nav.settings'));
    }
}
