<?php

namespace Tests\Feature;

use App\Livewire\Admin\Partners;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\Partner;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPartnerWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_credit_partner_wallet_and_ledger_records_balance(): void
    {
        $admin = $this->makeAdmin();
        $partner = $this->makePartner(balance: '25.00');

        $this->actingAs($admin, 'admin');

        Livewire::test(Partners::class)
            ->call('openWallet', $partner->id)
            ->set('walletDirection', 'credit')
            ->set('walletAmount', '15.50')
            ->set('walletNote', 'Manual bonus')
            ->call('adjustWallet')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame('40.50', (string) $partner->fresh()->balance);
        $this->assertSame('80.00', (string) $partner->fresh()->total_earned);

        $this->assertDatabaseHas('transactions', [
            'transactable_type' => Partner::class,
            'transactable_id' => $partner->id,
            'type' => Transaction::TYPE_CREDIT,
            'category' => Transaction::CATEGORY_ADMIN_CREDIT,
            'amount' => '15.50',
            'balance_before' => '25.00',
            'balance_after' => '40.50',
            'description' => 'Manual bonus',
        ]);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_PARTNER_BALANCE_CREDITED,
            'guard' => 'admin',
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_can_debit_partner_wallet_and_ledger_records_remaining_balance(): void
    {
        $admin = $this->makeAdmin();
        $partner = $this->makePartner(balance: '40.00');

        $this->actingAs($admin, 'admin');

        Livewire::test(Partners::class)
            ->call('openWallet', $partner->id)
            ->set('walletDirection', 'debit')
            ->set('walletAmount', '12.00')
            ->set('walletNote', 'Correction')
            ->call('adjustWallet')
            ->assertHasNoErrors();

        $this->assertSame('28.00', (string) $partner->fresh()->balance);

        $this->assertDatabaseHas('transactions', [
            'transactable_type' => Partner::class,
            'transactable_id' => $partner->id,
            'type' => Transaction::TYPE_DEBIT,
            'category' => Transaction::CATEGORY_ADMIN_DEBIT,
            'amount' => '12.00',
            'balance_before' => '40.00',
            'balance_after' => '28.00',
            'description' => 'Correction',
        ]);
    }

    public function test_admin_cannot_debit_more_than_available_balance(): void
    {
        $admin = $this->makeAdmin();
        $partner = $this->makePartner(balance: '10.00');

        $this->actingAs($admin, 'admin');

        Livewire::test(Partners::class)
            ->call('openWallet', $partner->id)
            ->set('walletDirection', 'debit')
            ->set('walletAmount', '25.00')
            ->set('walletNote', 'Too much')
            ->call('adjustWallet')
            ->assertDispatched('toast');

        $this->assertSame('10.00', (string) $partner->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_saving_partner_profile_does_not_overwrite_wallet(): void
    {
        $admin = $this->makeAdmin();
        $partner = $this->makePartner(balance: '33.25');

        $this->actingAs($admin, 'admin');

        Livewire::test(Partners::class)
            ->call('openProfile', $partner->id)
            ->set('editName', 'Renamed Partner')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame('Renamed Partner', $partner->fresh()->name);
        $this->assertSame('33.25', (string) $partner->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    protected function makePartner(string $balance = '0.00'): Partner
    {
        return Partner::query()->create([
            'name' => 'Wallet Partner',
            'email' => 'wallet-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => $balance,
            'total_earned' => '80.00',
        ]);
    }

    protected function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Wallet Admin',
            'email' => 'wallet-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }
}
