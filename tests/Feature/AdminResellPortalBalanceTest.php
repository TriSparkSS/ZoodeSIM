<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Statistics;
use App\Models\Admin;
use App\Models\ApiLog;
use App\Services\ResellPortal\ResellPortalBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class AdminResellPortalBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_statistics_load_hits_balance_api_and_second_uses_cache(): void
    {
        Http::fake([
            '*/balance' => Http::sequence()
                ->push(['success' => true, 'balance' => 150.00, 'currency' => 'USD'], 200)
                ->push(['success' => true, 'balance' => 175.50, 'currency' => 'USD'], 200),
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Statistics::class)
            ->assertSee('ResellPortal balance')
            ->assertSee('150.00 USD')
            ->assertDontSee('175.50')
            ->assertDontSee(__('admin.statistics.balance_cached'));

        $this->assertNoCredentialLeak(Livewire::test(Statistics::class)->html());

        Http::assertSentCount(1);

        Livewire::test(Statistics::class)
            ->assertSee('150.00 USD')
            ->assertSee(__('admin.statistics.balance_cached'))
            ->assertDontSee('175.50');

        Http::assertSentCount(1);

        $cached = Cache::get(ResellPortalBalanceService::CACHE_KEY);
        $this->assertIsArray($cached);
        $this->assertSame('150.00', $cached['amount']);
        $this->assertSame('USD', $cached['currency']);
        $this->assertArrayNotHasKey('api_key', $cached);
        $this->assertArrayNotHasKey('api_secret', $cached);
        $this->assertNoCredentialLeak((string) json_encode($cached));
        $this->assertStoredLogsHaveNoCredentials();
    }

    public function test_refresh_forgets_cache_and_calls_the_api_again(): void
    {
        Http::fake([
            '*/balance' => Http::sequence()
                ->push(['success' => true, 'balance' => 150.00, 'currency' => 'USD'], 200)
                ->push(['success' => true, 'balance' => 175.50, 'currency' => 'USD'], 200),
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Statistics::class)
            ->assertSee('150.00 USD')
            ->call('refreshBalance')
            ->assertSee('175.50 USD')
            ->assertDispatched('toast');

        Http::assertSentCount(2);
        $this->assertSame('175.50', Cache::get(ResellPortalBalanceService::CACHE_KEY)['amount'] ?? null);
    }

    public function test_dashboard_shows_statistics_and_shared_api_balance_cache(): void
    {
        Http::fake([
            '*/balance' => Http::sequence()
                ->push(['success' => true, 'balance' => 150.00, 'currency' => 'USD'], 200)
                ->push(['success' => true, 'balance' => 175.50, 'currency' => 'USD'], 200),
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Dashboard::class)
            ->assertSee(__('admin.dashboard.title'))
            ->assertSee('150.00 USD')
            ->assertSee(__('admin.statistics.total_partners'))
            ->assertSee(__('admin.statistics.total_registrations'))
            ->assertSee(__('admin.statistics.total_payouts'))
            ->assertSee(__('admin.statistics.active_codes'))
            ->assertDontSee('175.50');

        Http::assertSentCount(1);

        Livewire::test(Statistics::class)
            ->assertSee('150.00 USD')
            ->assertSee(__('admin.statistics.balance_cached'))
            ->assertDontSee('175.50');

        Http::assertSentCount(1);

        Livewire::test(Dashboard::class)
            ->call('refreshBalance')
            ->assertSee('175.50 USD')
            ->assertDispatched('toast');

        Http::assertSentCount(2);
        $this->assertNoCredentialLeak(Livewire::test(Dashboard::class)->html());
    }

    public function test_unauthorized_balance_still_renders_statistics(): void
    {
        Http::fake([
            '*/balance' => Http::response(['success' => false, 'message' => 'Invalid API Key'], 401),
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Statistics::class)
            ->assertSuccessful()
            ->assertSee(__('admin.statistics.resellportal_balance'))
            ->assertSee(__('admin.statistics.balance_unavailable'))
            ->assertSee(__('admin.statistics.total_partners'))
            ->assertDontSee('Invalid API Key');

        $this->assertNoCredentialLeak(Livewire::test(Statistics::class)->html());
        Http::assertSentCount(1);

        Livewire::test(Statistics::class)
            ->call('refreshBalance')
            ->assertSee(__('admin.statistics.balance_unavailable'))
            ->assertDispatched('toast');

        Http::assertSentCount(2);
        $this->assertStoredLogsHaveNoCredentials();
    }

    public function test_timeout_still_renders_statistics_unavailable(): void
    {
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Statistics::class)
            ->assertSuccessful()
            ->assertSee(__('admin.statistics.balance_unavailable'))
            ->assertSee(__('admin.statistics.total_partners'));

        $this->assertSame(1, $attempts);
        $this->assertNoCredentialLeak(Livewire::test(Statistics::class)->html());
        $this->assertSame(1, $attempts);
        $this->assertFalse(Cache::get(ResellPortalBalanceService::CACHE_KEY)['available'] ?? true);
        $this->assertStoredLogsHaveNoCredentials();
    }

    protected function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Balance Admin',
            'email' => 'balance-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }

    protected function assertNoCredentialLeak(string $content): void
    {
        $this->assertStringNotContainsString('test-api-key', $content);
        $this->assertStringNotContainsString('test-api-secret', $content);
    }

    protected function assertStoredLogsHaveNoCredentials(): void
    {
        foreach (Log::sharedContext() as $value) {
            $this->assertNoCredentialLeak((string) json_encode($value));
        }

        foreach (ApiLog::query()->get() as $log) {
            $this->assertNoCredentialLeak((string) json_encode($log->toArray()));
        }
    }
}
