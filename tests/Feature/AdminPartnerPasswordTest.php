<?php

namespace Tests\Feature;

use App\Livewire\Admin\Partners;
use App\Models\Admin;
use App\Models\AuthSession;
use App\Models\Partner;
use App\Services\Partner\PartnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPartnerPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_partner_password(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $partner = Partner::query()->create([
            'name' => 'Partner One',
            'email' => 'partner1@example.com',
            'password' => 'old-password',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(Partners::class)
            ->call('openPassword', $partner->id)
            ->set('editPassword', 'new-password123')
            ->set('editPasswordConfirmation', 'new-password123')
            ->call('savePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password123', $partner->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $partner->fresh()->password));
    }

    public function test_password_change_revokes_partner_sessions(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Partner One',
            'email' => 'partner1@example.com',
            'password' => 'old-password',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $session = AuthSession::query()->create([
            'authenticatable_type' => $partner->getMorphClass(),
            'authenticatable_id' => $partner->id,
            'guard' => 'partner',
            'session_id' => 'partner-session-1',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'device_label' => 'Test',
            'login_at' => now()->subHour(),
            'last_activity_at' => now()->subHour(),
        ]);

        app(PartnerService::class)->updatePassword($partner, 'brand-new-pass', true);

        $this->assertNotNull($session->fresh()->revoked_at);
        $this->assertTrue(Hash::check('brand-new-pass', $partner->fresh()->password));
    }

    public function test_leaving_password_blank_keeps_existing_password(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $partner = Partner::query()->create([
            'name' => 'Partner One',
            'email' => 'partner1@example.com',
            'password' => 'keep-this-password',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(Partners::class)
            ->call('openProfile', $partner->id)
            ->set('editName', 'Partner Renamed')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('keep-this-password', $partner->fresh()->password));
        $this->assertSame('Partner Renamed', $partner->fresh()->name);
    }
}
