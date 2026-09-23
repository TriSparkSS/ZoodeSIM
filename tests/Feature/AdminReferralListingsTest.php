<?php

namespace Tests\Feature;

use App\Livewire\Admin\PartnerReferralHistory;
use App\Livewire\Admin\Partners;
use App\Livewire\Admin\PromoAuditLogs;
use App\Livewire\Admin\UserReferralHistory;
use App\Livewire\Admin\Users;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\PromoAuditLog;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminReferralListingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeResellPortalClientCreate();
    }

    public function test_user_referral_redemption_is_written_to_the_promo_audit_log(): void
    {
        $referrer = User::factory()->create([
            'name' => 'Referrer User',
            'email' => 'referrer@example.com',
            'phone' => '+15551111921',
        ]);

        $this->postJson('/api/user/register', [
            'name' => 'Ada Lovelace',
            'email' => 'user@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_id' => 'audit-referral-device',
            'referral_code' => $referrer->referral_code,
        ])->assertCreated();

        $invitee = User::query()->where('email', 'user@example.com')->firstOrFail();
        $referral = UserReferral::query()->where('referred_id', $invitee->id)->firstOrFail();
        $log = PromoAuditLog::query()
            ->where('action', PromoAuditLog::ACTION_REDEEMED)
            ->where('code', $referrer->referral_code)
            ->firstOrFail();

        $this->assertNull($log->partner_id);
        $this->assertNull($log->promo_code_id);
        $this->assertSame($invitee->id, $log->actor_id);
        $this->assertSame($referral->id, $log->meta['user_referral_id']);
        $this->assertSame($referrer->id, $log->meta['referrer_id']);
        $this->assertSame('Referrer User', $log->meta['referrer_name']);
        $this->assertSame($invitee->id, $log->meta['referred_id']);
        $this->assertSame('1.00', $log->meta['referrer_amount']);
        $this->assertSame('1.50', $log->meta['referred_amount']);

        $admin = Admin::query()->create([
            'name' => 'Audit Admin',
            'email' => 'referral-audit@zoodesim.test',
            'password' => 'password123',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(PromoAuditLogs::class)
            ->set('search', 'Referrer User')
            ->assertSee($referrer->referral_code)
            ->assertSee('Referrer User');
    }

    public function test_existing_user_referrals_are_backfilled_into_the_promo_audit_log(): void
    {
        $referrer = User::factory()->create([
            'name' => 'Legacy Referrer',
            'phone' => '+15551111922',
        ]);
        $invitee = User::factory()->create([
            'name' => 'Legacy Invitee',
            'phone' => '+15551111923',
        ]);
        $referral = UserReferral::query()->create([
            'referrer_id' => $referrer->id,
            'referred_id' => $invitee->id,
            'referrer_amount' => '1.00',
            'referred_amount' => '1.50',
            'currency' => 'USD',
        ]);
        $referral->forceFill(['created_at' => '2026-01-15 10:00:00'])->save();

        $migration = require database_path('migrations/2026_09_23_222600_backfill_user_referral_promo_audit_logs.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, PromoAuditLog::query()->where('code', $referrer->referral_code)->count());

        $log = PromoAuditLog::query()->where('meta->user_referral_id', $referral->id)->firstOrFail();
        $this->assertSame(PromoAuditLog::ACTION_REDEEMED, $log->action);
        $this->assertNull($log->partner_id);
        $this->assertTrue($log->created_at->equalTo($referral->fresh()->created_at));
        $this->assertSame('1.00', $log->meta['referrer_amount']);
        $this->assertSame('1.50', $log->meta['referred_amount']);
    }

    public function test_users_referrals_modal_lists_referred_user_and_earning(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Users Admin',
            'email' => 'users-referrals@zoodesim.test',
            'password' => 'password123',
        ]);
        $referrer = User::factory()->create([
            'name' => 'Host User',
            'phone' => '+15551111924',
        ]);
        $invitee = User::factory()->create([
            'name' => 'Guest User',
            'email' => 'guest-referral@example.com',
            'phone' => '+15551111925',
        ]);
        UserReferral::query()->create([
            'referrer_id' => $referrer->id,
            'referred_id' => $invitee->id,
            'referrer_amount' => '1.00',
            'referred_amount' => '1.50',
            'currency' => 'USD',
        ]);

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.users.referrals', $referrer))
            ->assertOk()
            ->assertSee('Guest User')
            ->assertSee('guest-referral@example.com')
            ->assertSee('$1.00')
            ->assertSee(__('admin.users.referrals_back'));
    }

    public function test_user_referral_history_and_user_list_are_paginated(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Page Admin',
            'email' => 'page-admin@zoodesim.test',
            'password' => 'password123',
        ]);
        $referrer = User::factory()->create([
            'name' => 'Paging Referrer',
            'phone' => '+15551111940',
        ]);

        foreach (range(1, 21) as $index) {
            $invitee = User::factory()->create([
                'name' => sprintf('Paged Invitee %02d', $index),
                'phone' => sprintf('+1555200%04d', $index),
            ]);
            $referral = UserReferral::query()->create([
                'referrer_id' => $referrer->id,
                'referred_id' => $invitee->id,
                'referrer_amount' => '1.00',
                'referred_amount' => '1.50',
                'currency' => 'USD',
            ]);
            $referral->forceFill(['created_at' => now()->subMinutes(30 - $index)])->save();
        }

        $this->actingAs($admin, 'admin');

        Livewire::test(UserReferralHistory::class, ['user' => $referrer->id])
            ->assertSee('Paged Invitee 21')
            ->assertDontSee('Paged Invitee 01')
            ->call('gotoPage', 2)
            ->assertSee('Paged Invitee 01')
            ->assertDontSee('Paged Invitee 21');
    }

    public function test_users_listing_is_paginated(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Users Page Admin',
            'email' => 'users-page-admin@zoodesim.test',
            'password' => 'password123',
        ]);
        $oldest = User::factory()->create([
            'name' => 'Oldest Listed User',
            'phone' => '+15551111941',
        ]);
        $oldest->forceFill(['created_at' => now()->subDays(3)])->save();
        User::factory()->count(20)->create();

        $this->actingAs($admin, 'admin');

        Livewire::test(Users::class)
            ->assertDontSee('Oldest Listed User')
            ->call('gotoPage', 2)
            ->assertSee('Oldest Listed User');
    }

    public function test_partners_referrals_modal_lists_registration_and_earning(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Partners Admin',
            'email' => 'partners-referrals@zoodesim.test',
            'password' => 'password123',
        ]);
        $partner = Partner::query()->create([
            'name' => 'Listing Partner',
            'email' => 'listing-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
        $promo = PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'LIST10',
            'bonus_mb' => 200,
            'partner_reward' => 4.25,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 1,
            'max_usage' => null,
        ]);
        $user = User::factory()->create([
            'name' => 'Promo Guest',
            'email' => 'promo-guest@example.com',
            'phone' => '+15551111926',
        ]);
        PromoUsage::query()->create([
            'promo_code_id' => $promo->id,
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'bonus_mb_given' => 200,
            'partner_reward' => 4.25,
            'used_at' => now(),
        ]);

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.partners.referrals', $partner))
            ->assertOk()
            ->assertSee('Promo Guest')
            ->assertSee('promo-guest@example.com')
            ->assertSee('LIST10')
            ->assertSee('$4.25')
            ->assertSee(__('admin.partners.referrals_back'));

        foreach (range(1, 21) as $index) {
            $extra = Partner::query()->create([
                'name' => $index === 1 ? 'Oldest Listed Partner' : 'Paged Partner '.$index,
                'email' => "paged-partner-{$index}@example.com",
                'password' => 'password123',
                'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
                'status' => 'active',
                'balance' => 0,
                'total_earned' => 0,
            ]);
            $extra->forceFill(['created_at' => now()->subMinutes(40 - $index)])->save();
        }

        Livewire::test(Partners::class)
            ->assertDontSee('Oldest Listed Partner')
            ->call('gotoPage', 2)
            ->assertSee('Oldest Listed Partner');
    }
}
