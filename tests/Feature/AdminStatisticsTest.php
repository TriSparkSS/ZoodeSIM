<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerApplication;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Admin\AdminStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_uses_database_counts(): void
    {
        $partner = $this->makePartner('Stats Partner');
        PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'STATS100',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 3,
            'max_usage' => null,
        ]);

        Withdrawal::query()->create([
            'partner_id' => $partner->id,
            'amount' => 25.50,
            'method' => 'paypal',
            'status' => 'completed',
            'requested_at' => now()->subDay(),
            'completed_at' => now(),
        ]);

        Withdrawal::query()->create([
            'partner_id' => $partner->id,
            'amount' => 10,
            'method' => 'paypal',
            'status' => 'pending',
            'requested_at' => now(),
            'completed_at' => null,
        ]);

        $summary = app(AdminStatisticsService::class)->summary();

        $this->assertSame(1, $summary['total_partners']);
        $this->assertSame(3, $summary['total_registrations']);
        $this->assertSame(25.50, $summary['total_payouts']);
        $this->assertSame(1, $summary['active_codes']);
    }

    public function test_top_partners_and_recent_applications_are_dynamic(): void
    {
        $partner = $this->makePartner('Top Partner');
        PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'TOP100',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 12,
            'max_usage' => null,
        ]);

        PartnerApplication::query()->create([
            'first_name' => 'Recent',
            'last_name' => 'Applicant',
            'email' => 'recent@example.com',
            'phone' => '+1234567890',
            'platforms' => ['telegram'],
            'telegram' => 'https://t.me/recent',
            'instagram' => null,
            'tiktok' => null,
            'youtube' => null,
            'followers' => '10k-50k',
            'niche' => 'travel',
            'country' => 'tajikistan',
            'about' => 'About',
            'status' => 'pending',
        ]);

        $service = app(AdminStatisticsService::class);
        $top = $service->topPartners();
        $apps = $service->recentApplications();

        $this->assertSame('Top Partner', $top[0]['name']);
        $this->assertSame(12, $top[0]['registrations']);
        $this->assertSame('TOP100', $top[0]['promo']);
        $this->assertSame('Recent Applicant', $apps[0]['name']);
    }

    public function test_monthly_trend_includes_promo_usage(): void
    {
        $partner = $this->makePartner('Trend Partner');
        $promo = PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'TREND10',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 1,
            'max_usage' => null,
        ]);

        $user = User::factory()->create();

        PromoUsage::query()->create([
            'promo_code_id' => $promo->id,
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'bonus_mb_given' => 200,
            'partner_reward' => 1.50,
            'used_at' => now(),
        ]);

        $trend = app(AdminStatisticsService::class)->monthlyTrend(6);
        $current = collect($trend)->firstWhere('month', (int) now()->month);

        $this->assertNotNull($current);
        $this->assertSame(1, $current['registrations']);
        $this->assertEquals(1.50, $current['earnings']);
    }

    protected function makePartner(string $name): Partner
    {
        return Partner::query()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 40,
            'total_earned' => 120,
        ]);
    }
}
