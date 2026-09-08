<?php

namespace Tests\Feature;

use App\Livewire\Admin\Transactions;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_transactions(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $partner = $this->makePartner();
        $promo = PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'FILTER10',
            'bonus_mb' => 100,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $ledger = app(WalletLedgerServiceInterface::class);

        $userCredit = $ledger->credit(
            $user,
            Money::fromDecimal('100', 'MB'),
            Transaction::CATEGORY_PROMO_BONUS,
            'promo_usage',
            'usage-1',
            'Promo registration bonus',
            promoCodeId: $promo->id,
            meta: ['promo_code' => 'FILTER10'],
        );

        $partnerCredit = $ledger->credit(
            $partner,
            Money::fromDecimal('1.50', 'USD'),
            Transaction::CATEGORY_PROMO_REWARD,
            'promo_usage',
            'usage-1',
            'Referral registration commission',
            countsAsEarning: true,
            promoCodeId: $promo->id,
            meta: ['promo_code' => 'FILTER10', 'commission' => '1.50'],
        );

        $partnerDebit = $ledger->debit(
            $partner,
            Money::fromDecimal('1.50', 'USD'),
            Transaction::CATEGORY_ADMIN_DEBIT,
            'admin',
            $admin->id,
            'Manual correction',
        );

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.transactions'))
            ->assertOk()
            ->assertSee($userCredit->transaction_id)
            ->assertSee($partnerCredit->transaction_id)
            ->assertSee($partnerDebit->transaction_id);

        Livewire::test(Transactions::class)
            ->assertSee('Ada Lovelace')
            ->assertSee('Filter Partner')
            ->assertSee($userCredit->transaction_id)
            ->assertSee($partnerCredit->transaction_id)
            ->assertSee($partnerDebit->transaction_id)
            ->set('transactionId', $userCredit->transaction_id)
            ->assertSee('Ada Lovelace')
            ->assertDontSee('Filter Partner')
            ->set('transactionId', '')
            ->set('user', 'ada@example.com')
            ->assertSee('Ada Lovelace')
            ->assertDontSee('Filter Partner')
            ->set('user', '')
            ->set('partner', 'Filter Partner')
            ->assertSee('Filter Partner')
            ->assertDontSee('Ada Lovelace')
            ->set('partner', '')
            ->set('type', Transaction::TYPE_DEBIT)
            ->assertSee($partnerDebit->transaction_id)
            ->assertDontSee($userCredit->transaction_id)
            ->set('type', '')
            ->set('category', Transaction::CATEGORY_PROMO_REWARD)
            ->assertSee($partnerCredit->transaction_id)
            ->assertDontSee($userCredit->transaction_id)
            ->set('category', '')
            ->set('promo', 'FILTER10')
            ->assertSee($userCredit->transaction_id)
            ->assertSee($partnerCredit->transaction_id)
            ->assertDontSee($partnerDebit->transaction_id)
            ->set('promo', '')
            ->set('amountMin', '2')
            ->assertSee($userCredit->transaction_id)
            ->assertDontSee($partnerCredit->transaction_id)
            ->set('amountMin', '')
            ->set('currency', 'mb')
            ->assertSee($userCredit->transaction_id)
            ->assertDontSee($partnerCredit->transaction_id)
            ->set('currency', 'amount')
            ->assertSee($partnerCredit->transaction_id)
            ->assertSee($partnerDebit->transaction_id)
            ->assertDontSee($userCredit->transaction_id)
            ->set('currency', '')
            ->set('status', Transaction::STATUS_COMPLETED)
            ->assertSee($userCredit->transaction_id)
            ->assertSee($partnerDebit->transaction_id);
    }

    public function test_guest_cannot_open_transactions(): void
    {
        $this->get(route('admin.transactions'))->assertRedirect(route('admin.login'));
    }

    public function test_partner_cannot_open_transactions(): void
    {
        $this->actingAs($this->makePartner(), 'partner')
            ->get(route('admin.transactions'))
            ->assertForbidden();
    }

    protected function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Txn Admin',
            'email' => 'txn-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }

    protected function makePartner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Filter Partner',
            'email' => 'filter-partner@zoodesim.test',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }
}
