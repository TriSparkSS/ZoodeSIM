<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_credit_user_wallet_and_ledger_records_balance(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['balance' => '25.00']);

        $this->actingAs($admin, 'admin');

        Livewire::test(Users::class)
            ->call('openWallet', $user->id)
            ->set('walletDirection', 'credit')
            ->set('walletAmount', '15.50')
            ->set('walletNote', 'Manual bonus')
            ->call('adjustWallet')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame('40.50', (string) $user->fresh()->balance);

        $this->assertDatabaseHas('transactions', [
            'transactable_type' => User::class,
            'transactable_id' => $user->id,
            'type' => Transaction::TYPE_CREDIT,
            'category' => Transaction::CATEGORY_ADMIN_CREDIT,
            'amount' => '15.50',
            'balance_before' => '25.00',
            'balance_after' => '40.50',
            'description' => 'Manual bonus',
        ]);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_USER_BALANCE_CREDITED,
            'guard' => 'admin',
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_can_debit_user_wallet_and_ledger_records_remaining_balance(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['balance' => '40.00']);

        $this->actingAs($admin, 'admin');

        Livewire::test(Users::class)
            ->call('openWallet', $user->id)
            ->set('walletDirection', 'debit')
            ->set('walletAmount', '12.00')
            ->set('walletNote', 'Correction')
            ->call('adjustWallet')
            ->assertHasNoErrors();

        $this->assertSame('28.00', (string) $user->fresh()->balance);

        $this->assertDatabaseHas('transactions', [
            'transactable_type' => User::class,
            'transactable_id' => $user->id,
            'type' => Transaction::TYPE_DEBIT,
            'category' => Transaction::CATEGORY_ADMIN_DEBIT,
            'amount' => '12.00',
            'balance_before' => '40.00',
            'balance_after' => '28.00',
            'description' => 'Correction',
        ]);
    }

    public function test_admin_cannot_debit_more_than_available_user_balance(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['balance' => '10.00']);

        $this->actingAs($admin, 'admin');

        Livewire::test(Users::class)
            ->call('openWallet', $user->id)
            ->set('walletDirection', 'debit')
            ->set('walletAmount', '25.00')
            ->set('walletNote', 'Too much')
            ->call('adjustWallet')
            ->assertDispatched('toast');

        $this->assertSame('10.00', (string) $user->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_wallet_history_excludes_mb_promo_bonus_and_shows_bonus_mb(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['balance' => '0.00', 'bonus_mb' => 0]);
        $ledger = app(WalletLedgerServiceInterface::class);

        $ledger->credit(
            $user,
            Money::fromDecimal('200', 'MB'),
            Transaction::CATEGORY_PROMO_BONUS,
            'promo_usage',
            'usage-wallet-mb',
            'Promo registration bonus',
        );

        $ledger->credit(
            $user,
            Money::fromDecimal('15.00', 'USD'),
            Transaction::CATEGORY_ADMIN_CREDIT,
            'admin',
            $admin->id,
            'Manual top-up',
        );

        $this->actingAs($admin, 'admin');

        Livewire::test(Users::class)
            ->call('openWallet', $user->id)
            ->assertSet('walletBonusMb', 200)
            ->assertSet('walletBalance', '15.00')
            ->assertSee('200 MB')
            ->assertSee('Manual top-up')
            ->assertSee('+$15.00')
            ->assertDontSee('Promo registration bonus')
            ->assertDontSee('+$200.00');
    }

    protected function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'User Wallet Admin',
            'email' => 'user-wallet-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }
}
