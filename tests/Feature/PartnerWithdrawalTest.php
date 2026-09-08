<?php

namespace Tests\Feature;

use App\DataTransferObjects\CreateWithdrawalRequestData;
use App\Livewire\Admin\Payouts;
use App\Livewire\Partner\Earnings;
use App\Livewire\Partner\Settings;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Partner\Contracts\WithdrawalServiceInterface;
use App\Services\Promo\PromoRedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_request_withdrawal_and_balance_is_held(): void
    {
        $partner = $this->makePartner(balance: '120.00', earned: '120.00');

        $this->actingAs($partner, 'partner');

        Livewire::test(Earnings::class)
            ->call('openWithdrawModal')
            ->set('withdrawAmount', '80.00')
            ->set('withdrawMethod', Withdrawal::METHOD_CARD)
            ->set('withdrawDetails', 'partner@example.com')
            ->call('submitWithdrawal')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $partner->refresh();
        $this->assertSame('40.00', (string) $partner->balance);
        $this->assertSame('120.00', (string) $partner->total_earned);

        $this->assertDatabaseHas('withdrawals', [
            'partner_id' => $partner->id,
            'amount' => '80.00',
            'method' => Withdrawal::METHOD_CARD,
            'status' => Withdrawal::STATUS_PROCESSING,
            'payout_details' => 'partner@example.com',
        ]);

        $this->assertDatabaseHas('transactions', [
            'transactable_type' => Partner::class,
            'transactable_id' => $partner->id,
            'type' => Transaction::TYPE_DEBIT,
            'category' => Transaction::CATEGORY_WITHDRAWAL_HOLD,
            'amount' => '80.00',
            'balance_before' => '120.00',
            'balance_after' => '40.00',
        ]);

        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_WITHDRAWAL_REQUESTED,
            'guard' => 'partner',
            'authenticatable_id' => $partner->id,
        ]);
    }

    public function test_request_below_minimum_is_rejected(): void
    {
        $partner = $this->makePartner(balance: '120.00');

        $this->expectException(ValidationException::class);

        app(WithdrawalServiceInterface::class)->request($partner, new CreateWithdrawalRequestData(
            amount: '10.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $partner->email,
        ));
    }

    public function test_request_with_insufficient_balance_is_rejected(): void
    {
        $partner = $this->makePartner(balance: '40.00');

        $this->expectException(ValidationException::class);

        app(WithdrawalServiceInterface::class)->request($partner, new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $partner->email,
        ));
    }

    public function test_second_open_request_is_rejected(): void
    {
        $partner = $this->makePartner(balance: '200.00');
        $service = app(WithdrawalServiceInterface::class);

        $service->request($partner, new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $partner->email,
        ));

        $this->expectException(ValidationException::class);

        $service->request($partner->fresh(), new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_BANK_TRANSFER,
            payoutDetails: 'IBAN123',
        ));
    }

    public function test_admin_complete_does_not_debit_balance_again(): void
    {
        $partner = $this->makePartner(balance: '100.00');
        $admin = $this->makeAdmin();
        $service = app(WithdrawalServiceInterface::class);

        $withdrawal = $service->request($partner, new CreateWithdrawalRequestData(
            amount: '60.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $partner->email,
        ));

        $this->assertSame('40.00', (string) $partner->fresh()->balance);

        $this->actingAs($admin, 'admin');

        Livewire::test(Payouts::class)
            ->call('completePayout', $withdrawal->id)
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame('40.00', (string) $partner->fresh()->balance);
        $this->assertSame(Withdrawal::STATUS_COMPLETED, $withdrawal->fresh()->status);
        $this->assertSame(1, Transaction::query()
            ->where('transactable_type', Partner::class)
            ->where('transactable_id', $partner->id)
            ->count());
        $this->assertDatabaseHas('auth_activity_logs', [
            'event' => AuthActivityLog::EVENT_WITHDRAWAL_COMPLETED,
            'guard' => 'admin',
            'authenticatable_id' => $admin->id,
        ]);
    }

    public function test_admin_reject_refunds_held_balance(): void
    {
        $partner = $this->makePartner(balance: '100.00');
        $admin = $this->makeAdmin();
        $service = app(WithdrawalServiceInterface::class);

        $withdrawal = $service->request($partner, new CreateWithdrawalRequestData(
            amount: '60.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $partner->email,
        ));

        $this->actingAs($admin, 'admin');

        Livewire::test(Payouts::class)
            ->call('openRejectModal', $withdrawal->id)
            ->set('rejectNote', 'Invalid payout details')
            ->call('rejectPayout')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame('100.00', (string) $partner->fresh()->balance);
        $this->assertSame('100.00', (string) $partner->fresh()->total_earned);
        $this->assertSame(Withdrawal::STATUS_REJECTED, $withdrawal->fresh()->status);
        $this->assertSame('Invalid payout details', $withdrawal->fresh()->admin_note);

        $this->assertDatabaseHas('transactions', [
            'transactable_type' => Partner::class,
            'transactable_id' => $partner->id,
            'type' => Transaction::TYPE_CREDIT,
            'category' => Transaction::CATEGORY_WITHDRAWAL_REFUND,
            'amount' => '60.00',
            'balance_after' => '100.00',
        ]);
    }

    public function test_inactive_partner_cannot_request_withdrawal(): void
    {
        $partner = $this->makePartner(balance: '100.00', status: 'inactive');

        $this->expectException(ValidationException::class);

        app(WithdrawalServiceInterface::class)->request($partner, new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $partner->email,
        ));
    }

    public function test_partner_cannot_process_admin_payout_actions(): void
    {
        $partner = $this->makePartner(balance: '100.00');
        $withdrawal = app(WithdrawalServiceInterface::class)->request($partner, new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $partner->email,
        ));

        $this->actingAs($partner, 'partner');

        Livewire::test(Payouts::class)
            ->call('completePayout', $withdrawal->id)
            ->assertForbidden();

        $this->assertSame(Withdrawal::STATUS_PROCESSING, $withdrawal->fresh()->status);
        $this->assertSame('50.00', (string) $partner->fresh()->balance);
    }

    public function test_partner_can_persist_payout_settings(): void
    {
        $partner = $this->makePartner(balance: '80.00');

        $this->actingAs($partner, 'partner');

        Livewire::test(Settings::class)
            ->set('payoutMethod', Withdrawal::METHOD_PAYME)
            ->set('payoutDetails', 'payme-wallet-99')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $partner->refresh();
        $this->assertSame(Withdrawal::METHOD_PAYME, $partner->payout_method);
        $this->assertSame('payme-wallet-99', $partner->payout_details);
    }

    public function test_promo_redemption_writes_ledger_credit(): void
    {
        $partner = $this->makePartner(balance: '0.00', earned: '0.00');
        $promo = PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'LEDGER10',
            'bonus_mb' => 200,
            'partner_reward' => 2.25,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);
        $user = User::factory()->create();

        DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($user, $promo));

        $partner->refresh();
        $user->refresh();
        $this->assertSame('2.25', (string) $partner->balance);
        $this->assertSame('2.25', (string) $partner->total_earned);
        $this->assertSame(200, $user->bonus_mb);
        $this->assertDatabaseHas('transactions', [
            'transactable_type' => Partner::class,
            'transactable_id' => $partner->id,
            'type' => Transaction::TYPE_CREDIT,
            'category' => Transaction::CATEGORY_PROMO_REWARD,
            'amount' => '2.25',
            'balance_before' => '0.00',
            'balance_after' => '2.25',
        ]);
        $this->assertDatabaseHas('transactions', [
            'transactable_type' => User::class,
            'transactable_id' => $user->id,
            'type' => Transaction::TYPE_CREDIT,
            'category' => Transaction::CATEGORY_PROMO_BONUS,
            'amount' => '200.00',
            'currency' => 'MB',
            'balance_before' => '0.00',
            'balance_after' => '200.00',
        ]);
        $this->assertNotNull(Transaction::query()->where('transaction_id', 'like', 'TXN-%')->first());
    }

    public function test_partner_earnings_page_lists_own_wallet_transactions_only(): void
    {
        $partner = $this->makePartner(balance: '120.00', earned: '120.00');
        $other = Partner::query()->create([
            'name' => 'Other Partner',
            'email' => 'other-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => '50.00',
            'total_earned' => '50.00',
        ]);

        $service = app(WithdrawalServiceInterface::class);
        $own = $service->request($partner, new CreateWithdrawalRequestData(
            amount: '80.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $partner->email,
        ));
        $foreign = $service->request($other, new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: $other->email,
        ));

        $ownTxn = Transaction::query()
            ->where('reference_type', 'withdrawal')
            ->where('reference_id', $own->id)
            ->first();
        $foreignTxn = Transaction::query()
            ->where('reference_type', 'withdrawal')
            ->where('reference_id', $foreign->id)
            ->first();

        $this->assertNotNull($ownTxn);
        $this->assertNotNull($foreignTxn);

        $this->actingAs($partner, 'partner');

        Livewire::test(Earnings::class)
            ->assertSee($ownTxn->transaction_id)
            ->assertSee(__('partner.earnings.transactions'))
            ->assertDontSee($foreignTxn->transaction_id);
    }

    protected function makePartner(string $balance = '100.00', string $earned = '100.00', string $status = 'active'): Partner
    {
        return Partner::query()->create([
            'name' => 'Withdraw Partner',
            'email' => 'withdraw-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => $status,
            'balance' => $balance,
            'total_earned' => $earned,
        ]);
    }

    protected function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Payout Admin',
            'email' => 'payout-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }
}
