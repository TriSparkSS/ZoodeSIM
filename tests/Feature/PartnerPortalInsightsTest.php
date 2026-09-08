<?php

namespace Tests\Feature;

use App\Livewire\Partner\Dashboard;
use App\Livewire\Partner\Earnings;
use App\Livewire\Partner\Registrations;
use App\Livewire\Partner\Statistics;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Partner\PartnerPortalDataService;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerPortalInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_registrations_chart_buckets_last_seven_days(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);

        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Today One']), now());
        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Today Two']), now());
        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Yesterday User']), now()->subDay());

        $chart = app(PartnerPortalDataService::class)->dailyRegistrations($partner);

        $this->assertCount(7, $chart);

        $today = collect($chart)->firstWhere('label', self::DAY_LABELS[(int) now()->dayOfWeek]);
        $yesterday = collect($chart)->firstWhere('label', self::DAY_LABELS[(int) now()->subDay()->dayOfWeek]);

        $this->assertSame(2, $today['count']);
        $this->assertSame(100, $today['value']);
        $this->assertSame(1, $yesterday['count']);
        $this->assertSame(50, $yesterday['value']);
        $this->assertSame(3, collect($chart)->sum('count'));
    }

    public function test_registrations_use_real_names_and_this_month_count(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner, usageCount: 2);

        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Shahlo Karimova']), now());
        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Old Referral']), now()->subMonth());

        $portal = app(PartnerPortalDataService::class);

        $this->assertSame(1, $portal->registrationsThisMonth($partner));

        $rows = $portal->registrations($partner);
        $names = collect($rows)->pluck('name');

        $this->assertTrue($names->contains('Shahlo Karimova'));
        $this->assertTrue($names->contains('Old Referral'));
        $this->assertFalse($names->contains(fn (string $name) => str_starts_with($name, 'User ')));
        $this->assertSame('S', collect($rows)->firstWhere('name', 'Shahlo Karimova')['initial']);
        $this->assertSame(now()->toDateString(), collect($rows)->firstWhere('name', 'Shahlo Karimova')['date']);
    }

    public function test_registrations_can_filter_by_date_and_search(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);

        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Current User']), now());
        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Last Month User']), now()->subMonth());

        $portal = app(PartnerPortalDataService::class);

        $thisMonth = $portal->registrations(
            $partner,
            dateFrom: now()->startOfMonth()->toDateString(),
            dateTo: now()->toDateString(),
        );

        $this->assertSame(['Current User'], collect($thisMonth)->pluck('name')->all());

        $search = $portal->registrations($partner, search: 'last month');
        $this->assertSame(['Last Month User'], collect($search)->pluck('name')->all());
    }

    public function test_earnings_history_filters_by_period(): void
    {
        $partner = $this->makePartner();
        $ledger = app(WalletLedgerServiceInterface::class);
        $currency = (string) config('pricing.currency', 'USD');

        $today = $ledger->credit(
            $partner,
            Money::fromDecimal('1.50', $currency),
            Transaction::CATEGORY_PROMO_REWARD,
            description: 'Today registration',
            countsAsEarning: true,
        );
        $old = $ledger->credit(
            $partner,
            Money::fromDecimal('5.00', $currency),
            Transaction::CATEGORY_PURCHASE_COMMISSION,
            description: 'Old purchase',
            countsAsEarning: true,
        );
        $old->forceFill(['created_at' => now()->subDays(20)])->save();

        $portal = app(PartnerPortalDataService::class);

        $all = collect($portal->earningsHistory($partner, 'all'))->pluck('id');
        $month = collect($portal->earningsHistory($partner, 'month'))->pluck('id');
        $todayRows = collect($portal->earningsHistory($partner, 'today'))->pluck('id');

        $this->assertTrue($all->contains($today->id));
        $this->assertTrue($all->contains($old->id));
        $this->assertTrue($month->contains($today->id));
        $this->assertTrue($todayRows->contains($today->id));
        $this->assertFalse($todayRows->contains($old->id));
    }

    public function test_partner_pages_render_chart_names_this_month_and_period_filters(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner, usageCount: 2);

        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Nilufar Saidova']), now());
        $this->recordUsage($partner, $promo, User::factory()->create(['name' => 'Past Referral']), now()->subMonth());

        $ledger = app(WalletLedgerServiceInterface::class);
        $currency = (string) config('pricing.currency', 'USD');
        $ledger->credit(
            $partner,
            Money::fromDecimal('1.50', $currency),
            Transaction::CATEGORY_PROMO_REWARD,
            description: 'Fresh registration credit',
            countsAsEarning: true,
        );
        $old = $ledger->credit(
            $partner,
            Money::fromDecimal('30.00', $currency),
            Transaction::CATEGORY_REFERRAL_MILESTONE,
            description: 'Old milestone credit',
            countsAsEarning: true,
        );
        $old->forceFill(['created_at' => now()->subDays(40)])->save();

        $this->actingAs($partner, 'partner');

        Livewire::test(Dashboard::class)
            ->assertSee(__('partner.dashboard.registrations_chart'))
            ->assertSee(__('partner.dashboard.week_total', ['count' => 1]))
            ->assertSee('Nilufar Saidova');

        Livewire::test(Statistics::class)
            ->assertSee(__('partner.dashboard.registrations_chart'))
            ->assertSee(__('partner.dashboard.week_total', ['count' => 1]));

        Livewire::test(Registrations::class)
            ->assertSee('Nilufar Saidova')
            ->assertSee('Past Referral')
            ->assertSee('1')
            ->set('dateFrom', now()->startOfMonth()->toDateString())
            ->set('dateTo', now()->toDateString())
            ->assertSee('Nilufar Saidova')
            ->assertDontSee('Past Referral');

        Livewire::test(Earnings::class)
            ->assertSee('Fresh registration credit')
            ->assertSee('Old milestone credit')
            ->set('filter', 'today')
            ->assertSee('Fresh registration credit')
            ->assertDontSee('Old milestone credit');
    }

    /**
     * @var list<string>
     */
    private const DAY_LABELS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    protected function makePartner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Insight Partner',
            'email' => 'insight-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => '0.00',
            'total_earned' => '0.00',
        ]);
    }

    protected function makePromo(Partner $partner, int $usageCount = 0): PromoCode
    {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'INSIGHT10',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => $usageCount,
            'max_usage' => null,
        ]);
    }

    protected function recordUsage(Partner $partner, PromoCode $promo, User $user, $usedAt): PromoUsage
    {
        return PromoUsage::query()->create([
            'promo_code_id' => $promo->id,
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'bonus_mb_given' => 200,
            'partner_reward' => 1.50,
            'used_at' => $usedAt,
        ]);
    }
}
