<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserWalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_wallet(): void
    {
        $user = User::factory()->create(['balance' => '12.50']);
        Sanctum::actingAs($user);

        $this->getJson('/api/user/wallet')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('api.wallet.retrieved'))
            ->assertJsonPath('data.balance', '12.50')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.transactions', []);
    }

    public function test_unauthenticated_user_cannot_view_or_adjust_wallet(): void
    {
        $this->getJson('/api/user/wallet')->assertUnauthorized();
        $this->postJson('/api/user/wallet', [
            'direction' => 'credit',
            'amount' => '5.00',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_credit_and_debit_wallet(): void
    {
        $user = User::factory()->create(['balance' => '10.00']);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/wallet', [
            'direction' => 'credit',
            'amount' => '5.50',
            'note' => 'Top up',
        ])
            ->assertOk()
            ->assertJsonPath('data.balance', '15.50')
            ->assertJsonPath('data.transaction.category', Transaction::CATEGORY_WALLET_CREDIT)
            ->assertJsonPath('data.transaction.amount', '5.50');

        $this->assertSame('15.50', (string) $user->fresh()->balance);

        $this->postJson('/api/user/wallet', [
            'direction' => 'debit',
            'amount' => '3.00',
            'note' => 'Revert',
        ])
            ->assertOk()
            ->assertJsonPath('data.balance', '12.50')
            ->assertJsonPath('data.transaction.category', Transaction::CATEGORY_WALLET_DEBIT);

        $this->assertSame('12.50', (string) $user->fresh()->balance);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_user_cannot_debit_more_than_wallet_balance(): void
    {
        $user = User::factory()->create(['balance' => '2.00']);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/wallet', [
            'direction' => 'debit',
            'amount' => '5.00',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', __('api.wallet.insufficient_balance'));

        $this->assertSame('2.00', (string) $user->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_wallet_listing_omits_mb_promo_bonus_rows(): void
    {
        $user = User::factory()->create(['balance' => '12.50']);
        $ledger = app(WalletLedgerServiceInterface::class);

        $ledger->credit(
            $user,
            Money::fromDecimal('200', 'MB'),
            Transaction::CATEGORY_PROMO_BONUS,
            'promo_usage',
            'usage-api-mb',
            'Promo registration bonus',
        );

        $usd = $ledger->credit(
            $user,
            Money::fromDecimal('5.00', 'USD'),
            Transaction::CATEGORY_WALLET_CREDIT,
            'user',
            $user->id,
            'Top up',
        );

        Sanctum::actingAs($user);

        $this->getJson('/api/user/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', '17.50')
            ->assertJsonCount(1, 'data.transactions')
            ->assertJsonPath('data.transactions.0.transaction_id', $usd->transaction_id)
            ->assertJsonPath('data.transactions.0.category', Transaction::CATEGORY_WALLET_CREDIT);
    }

    public function test_profile_includes_wallet_balance(): void
    {
        $user = User::factory()->create(['balance' => '8.25']);
        Sanctum::actingAs($user);

        $this->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('data.balance', '8.25');
    }

    public function test_authenticated_user_can_list_transactions_with_pagination(): void
    {
        $user = User::factory()->create(['balance' => '0.00']);
        $other = User::factory()->create(['balance' => '0.00']);
        $ledger = app(WalletLedgerServiceInterface::class);

        $this->travelTo(now()->startOfSecond());

        $ledger->credit(
            $user,
            Money::fromDecimal('200', 'MB'),
            Transaction::CATEGORY_PROMO_BONUS,
            'promo_usage',
            'usage-list-mb',
            'Promo registration bonus',
        );

        $first = $ledger->credit(
            $user,
            Money::fromDecimal('5.00', 'USD'),
            Transaction::CATEGORY_WALLET_CREDIT,
            'user',
            $user->id,
            'First',
        );
        $this->travel(1)->second();
        $second = $ledger->credit(
            $user,
            Money::fromDecimal('7.00', 'USD'),
            Transaction::CATEGORY_WALLET_CREDIT,
            'user',
            $user->id,
            'Second',
        );
        $this->travel(1)->second();
        $third = $ledger->credit(
            $user,
            Money::fromDecimal('9.00', 'USD'),
            Transaction::CATEGORY_WALLET_CREDIT,
            'user',
            $user->id,
            'Third',
        );

        $ledger->credit(
            $other,
            Money::fromDecimal('11.00', 'USD'),
            Transaction::CATEGORY_WALLET_CREDIT,
            'user',
            $other->id,
            'Other user',
        );

        Sanctum::actingAs($user);

        $this->getJson('/api/user/transactions?per_page=2&page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('api.wallet.transactions_retrieved'))
            ->assertJsonCount(2, 'data.transactions')
            ->assertJsonPath('data.transactions.0.transaction_id', $third->transaction_id)
            ->assertJsonPath('data.transactions.1.transaction_id', $second->transaction_id)
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.per_page', 2)
            ->assertJsonPath('data.pagination.total', 3)
            ->assertJsonPath('data.pagination.last_page', 2)
            ->assertJsonPath('data.pagination.from', 1)
            ->assertJsonPath('data.pagination.to', 2);

        $this->getJson('/api/user/transactions?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data.transactions')
            ->assertJsonPath('data.transactions.0.transaction_id', $first->transaction_id)
            ->assertJsonPath('data.pagination.current_page', 2)
            ->assertJsonPath('data.pagination.from', 3)
            ->assertJsonPath('data.pagination.to', 3);
    }

    public function test_unauthenticated_user_cannot_list_transactions(): void
    {
        $this->getJson('/api/user/transactions')->assertUnauthorized();
    }

    public function test_transaction_list_rejects_invalid_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/transactions?per_page=51')
            ->assertUnprocessable();
    }
}
