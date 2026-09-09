<?php

namespace Tests\Feature;

use App\Livewire\Auth\AdminLogin;
use App\Livewire\Auth\PartnerLogin;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Policies\PromoCodePolicy;
use App\Services\Partner\PartnerPortalDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PortalAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_routes_require_authentication(): void
    {
        $this->get(route('partner.dashboard'))->assertRedirect(route('login'));
        $this->get(route('partner.promo-codes'))->assertRedirect(route('login'));
        $this->get(route('partner.earnings'))->assertRedirect(route('login'));
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->get(route('admin.applications'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_partner_can_login_and_access_partner_panel(): void
    {
        $partner = $this->makePartner('Partner One', 'partner1@example.com', 'password123');

        Livewire::test(PartnerLogin::class)
            ->set('email', 'partner1@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('partner.dashboard'));

        $this->assertAuthenticatedAs($partner, 'partner');
        $this->assertGuest('admin');

        $this->get(route('partner.dashboard'))->assertOk();
    }

    public function test_admin_can_login_and_access_admin_panel(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        Livewire::test(AdminLogin::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('partner');

        Http::fake([
            '*/balance' => Http::response([
                'success' => true,
                'balance' => 10.00,
                'currency' => 'USD',
            ], 200),
        ]);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.applications'))->assertOk();
    }

    public function test_partner_cannot_access_admin_panel(): void
    {
        $partner = $this->makePartner('Partner One', 'partner1@example.com', 'password123');

        $this->actingAs($partner, 'partner')
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($partner, 'partner')
            ->get(route('admin.applications'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_partner_panel_without_partner_session(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('partner.dashboard'))
            ->assertForbidden();
    }

    public function test_partner_login_rejects_invalid_credentials(): void
    {
        $this->makePartner('Partner One', 'partner1@example.com', 'password123');

        Livewire::test(PartnerLogin::class)
            ->set('email', 'partner1@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest('partner');
    }

    public function test_pending_partner_login_shows_waiting_for_approval(): void
    {
        $this->makePartner('Pending', 'pending@example.com', 'password123', 'pending');

        $login = Livewire::test(PartnerLogin::class)
            ->set('email', 'pending@example.com')
            ->set('password', 'password123')
            ->call('login');

        $login->assertHasErrors(['email']);
        $this->assertSame(__('auth.partner.pending_approval'), $login->errors()->first('email'));
        $this->assertGuest('partner');
    }

    public function test_blocked_partner_login_uses_generic_failed_message(): void
    {
        $this->makePartner('Blocked', 'blocked-login@example.com', 'password123', 'blocked');

        $login = Livewire::test(PartnerLogin::class)
            ->set('email', 'blocked-login@example.com')
            ->set('password', 'password123')
            ->call('login');

        $login->assertHasErrors(['email']);
        $this->assertSame(__('auth.failed'), $login->errors()->first('email'));
        $this->assertGuest('partner');
    }

    public function test_pending_partner_cannot_remain_authenticated(): void
    {
        $partner = $this->makePartner('Pending', 'pending-session@example.com', 'password123', 'pending');

        Auth::guard('partner')->login($partner);

        $this->get(route('partner.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email']);

        $this->assertGuest('partner');
        $this->assertSame(__('auth.partner.pending_approval'), session('errors')->first('email'));
    }

    public function test_blocked_partner_cannot_remain_authenticated(): void
    {
        $partner = $this->makePartner('Blocked', 'blocked@example.com', 'password123', 'blocked');

        Auth::guard('partner')->login($partner);

        $this->get(route('partner.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest('partner');
    }

    public function test_partner_cannot_access_another_partners_promo_code(): void
    {
        $owner = $this->makePartner('Owner', 'owner@example.com', 'password123');
        $intruder = $this->makePartner('Intruder', 'intruder@example.com', 'password123');

        $promo = PromoCode::query()->create([
            'partner_id' => $owner->id,
            'code' => 'OWNER100',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $policy = new PromoCodePolicy;
        $this->assertTrue($policy->view($owner, $promo));
        $this->assertFalse($policy->view($intruder, $promo));

        $portal = app(PartnerPortalDataService::class);
        $this->assertNull($portal->findOwnPromoCode($intruder, $promo->id));
        $this->assertNotNull($portal->findOwnPromoCode($owner, $promo->id));

        $intruderCodes = collect($portal->promoCodes($intruder))->pluck('code');
        $this->assertFalse($intruderCodes->contains('OWNER100'));
    }

    public function test_partner_login_clears_admin_guard(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $partner = $this->makePartner('Partner One', 'partner1@example.com', 'password123');

        Auth::guard('admin')->login($admin);

        Livewire::test(PartnerLogin::class)
            ->set('email', 'partner1@example.com')
            ->set('password', 'password123')
            ->call('login');

        $this->assertAuthenticatedAs($partner, 'partner');
        $this->assertGuest('admin');
    }

    protected function makePartner(
        string $name,
        string $email,
        string $password,
        string $status = 'active',
    ): Partner {
        return Partner::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'social_contacts' => [
                'telegram' => null,
                'instagram' => null,
                'twitter' => null,
            ],
            'status' => $status,
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }
}
