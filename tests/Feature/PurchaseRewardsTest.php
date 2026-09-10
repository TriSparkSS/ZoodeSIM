<?php

namespace Tests\Feature;

use App\DataTransferObjects\EsimPaymentResult;
use App\Livewire\Partner\Earnings;
use App\Models\EsimOrder;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Esim\Contracts\EsimPaymentGatewayInterface;
use App\Services\Promo\PromoRedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Concerns\FundsUserWallet;
use Tests\Concerns\SeedsDefaultPricingSlabs;
use Tests\TestCase;

class PurchaseRewardsTest extends TestCase
{
    use FundsUserWallet;
    use RefreshDatabase;
    use SeedsDefaultPricingSlabs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDefaultPricingSlabs();
    }

    /**
     * @return array<string, mixed>
     */
    protected function packagePayload(float $providerPrice = 1.80): array
    {
        return [
            'success' => true,
            'packages' => [[
                'package_code' => 'PHAJHEAYP',
                'name' => 'United States 1GB 7Days',
                'price' => $providerPrice,
                'location' => 'US',
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderPayload(): array
    {
        return [
            'success' => true,
            'service_id' => 789,
            'message' => 'E-SIM activated successfully',
            'package' => [
                'name' => 'United States 1GB 7Days',
                'location' => 'US',
            ],
            'amount_charged' => 1.80,
            'esim_details' => [
                'qr_code_url' => 'https://example.test/qr.png',
                'activation_url' => 'https://example.test/activate',
                'iccid' => '8901234567890123456',
                'esim_status' => 'active',
            ],
        ];
    }

    protected function fakeSuccessfulProvider(float $providerPrice = 1.80): void
    {
        Http::fake([
            '*/esim-packages*' => Http::response($this->packagePayload($providerPrice), 200),
            '*/clients' => Http::response(['success' => true, 'client_id' => 123], 200),
            '*/orders' => Http::response($this->orderPayload(), 200),
        ]);
    }

    public function test_first_paid_order_within_seven_days_applies_discount(): void
    {
        $this->fakeSuccessfulProvider();
        Sanctum::actingAs($this->fundedUser());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated()
            ->assertJsonPath('data.order.price', 1.8)
            ->assertJsonPath('data.order.list_price', 1.89)
            ->assertJsonPath('data.order.discount_amount', 0.09)
            ->assertJsonPath('data.order.discount_percentage', 5);

        $this->assertDatabaseHas('esim_orders', [
            'customer_price' => '1.89',
            'charged_amount' => '1.80',
            'discount_percentage' => '5.00',
        ]);
    }

    public function test_first_purchase_after_seven_days_has_no_discount(): void
    {
        $this->fakeSuccessfulProvider();
        Sanctum::actingAs($this->fundedUser([
            'created_at' => now()->subDays(8),
        ]));

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])
            ->assertCreated()
            ->assertJsonPath('data.order.price', 1.89)
            ->assertJsonPath('data.order.discount_amount', 0);

        $this->assertDatabaseHas('esim_orders', [
            'charged_amount' => '1.89',
            'discount_amount' => '0.00',
        ]);
    }

    public function test_second_paid_order_has_no_discount(): void
    {
        $this->fakeSuccessfulProvider();
        $user = $this->fundedUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();
        $firstId = EsimOrder::query()->value('id');
        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $first = EsimOrder::query()->find($firstId);
        $second = EsimOrder::query()->where('id', '!=', $firstId)->first();
        $this->assertSame('1.80', (string) $first->charged_amount);
        $this->assertSame('1.89', (string) $second->charged_amount);
        $this->assertSame('0.00', (string) $second->discount_amount);
    }

    public function test_cashback_credits_user_wallet_when_charged_above_ten(): void
    {
        $this->fakeSuccessfulProvider(50.00);
        $user = $this->fundedUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $user->refresh();
        $this->assertSame('455.54', (string) $user->balance);
        $this->assertDatabaseHas('transactions', [
            'transactable_type' => User::class,
            'transactable_id' => $user->id,
            'category' => Transaction::CATEGORY_ESIM_PURCHASE,
            'amount' => '49.40',
        ]);
        $this->assertDatabaseHas('transactions', [
            'transactable_type' => User::class,
            'transactable_id' => $user->id,
            'category' => Transaction::CATEGORY_PURCHASE_CASHBACK,
            'amount' => '4.94',
        ]);
    }

    public function test_cashback_is_not_granted_at_or_below_ten(): void
    {
        $this->fakeSuccessfulProvider(9.52);
        $user = $this->fundedUser([
            'created_at' => now()->subDays(8),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $order = EsimOrder::query()->first();
        $this->assertSame('10.00', (string) $order->charged_amount);
        $this->assertSame('490.00', (string) $user->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'category' => Transaction::CATEGORY_ESIM_PURCHASE,
            'amount' => '10.00',
        ]);
        $this->assertDatabaseMissing('transactions', [
            'category' => Transaction::CATEGORY_PURCHASE_CASHBACK,
        ]);
    }

    public function test_referred_first_paid_order_credits_ten_percent_commission(): void
    {
        $this->fakeSuccessfulProvider(50.00);
        [$user, $partner] = $this->registerReferredUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $partner->refresh();
        $this->assertSame('6.44', (string) $partner->balance);
        $this->assertSame('6.44', (string) $partner->total_earned);
        $this->assertDatabaseHas('transactions', [
            'transactable_type' => Partner::class,
            'transactable_id' => $partner->id,
            'category' => Transaction::CATEGORY_PURCHASE_COMMISSION,
            'amount' => '4.94',
        ]);
    }

    public function test_referred_second_paid_order_credits_five_percent_commission(): void
    {
        $this->fakeSuccessfulProvider(50.00);
        [$user, $partner] = $this->registerReferredUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();
        $firstId = EsimOrder::query()->value('id');
        $balanceAfterFirst = (string) $partner->fresh()->balance;

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $second = EsimOrder::query()->where('id', '!=', $firstId)->first();
        $this->assertSame('52.00', (string) $second->charged_amount);
        $this->assertDatabaseHas('transactions', [
            'transactable_id' => $partner->id,
            'category' => Transaction::CATEGORY_PURCHASE_COMMISSION,
            'amount' => '2.60',
            'reference_id' => $second->id,
        ]);
        $this->assertNotSame($balanceAfterFirst, (string) $partner->fresh()->balance);
    }

    public function test_unreferred_purchase_has_no_partner_commission(): void
    {
        $this->fakeSuccessfulProvider(50.00);
        Sanctum::actingAs($this->fundedUser());

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $this->assertDatabaseMissing('transactions', [
            'category' => Transaction::CATEGORY_PURCHASE_COMMISSION,
        ]);
    }

    public function test_failed_payment_writes_no_rewards(): void
    {
        $this->fakeSuccessfulProvider(50.00);
        [$user] = $this->registerReferredUser();
        Sanctum::actingAs($user);

        $this->mock(EsimPaymentGatewayInterface::class, function ($mock) {
            $mock->shouldReceive('settle')->once()->andReturn(EsimPaymentResult::failed('declined'));
        });

        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertStatus(402);

        $this->assertDatabaseMissing('transactions', [
            'category' => Transaction::CATEGORY_PURCHASE_CASHBACK,
        ]);
        $this->assertDatabaseMissing('transactions', [
            'category' => Transaction::CATEGORY_PURCHASE_COMMISSION,
        ]);
    }

    public function test_order_retry_does_not_double_credit_rewards(): void
    {
        $this->fakeSuccessfulProvider(50.00);
        [$user] = $this->registerReferredUser();
        Sanctum::actingAs($user);

        $headers = ['Idempotency-Key' => 'retry-key-1'];
        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'], $headers)->assertCreated();
        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'], $headers)->assertCreated();

        $this->assertSame(1, Transaction::query()->where('category', Transaction::CATEGORY_PURCHASE_CASHBACK)->count());
        $this->assertSame(1, Transaction::query()->where('category', Transaction::CATEGORY_PURCHASE_COMMISSION)->count());
        $this->assertSame(1, EsimOrder::query()->count());
    }

    public function test_tenth_referral_awards_milestone_once(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);

        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create([
                'email' => "ref{$i}@example.com",
                'phone' => '+1555000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            ]);
            DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($user, $promo));
        }

        $this->assertSame(1, Transaction::query()
            ->where('transactable_id', $partner->id)
            ->where('category', Transaction::CATEGORY_REFERRAL_MILESTONE)
            ->count());
        $this->assertDatabaseHas('transactions', [
            'transactable_id' => $partner->id,
            'category' => Transaction::CATEGORY_REFERRAL_MILESTONE,
            'amount' => '5.00',
        ]);

        $eleventh = User::factory()->create([
            'email' => 'ref11@example.com',
            'phone' => '+1555000011',
        ]);
        DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($eleventh, $promo->fresh()));

        $this->assertSame(1, Transaction::query()
            ->where('transactable_id', $partner->id)
            ->where('category', Transaction::CATEGORY_REFERRAL_MILESTONE)
            ->count());
    }

    public function test_fiftieth_and_hundredth_milestones_are_awarded(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);

        $this->seedUsages($partner, $promo, 49);
        $this->awardExistingMilestone($partner, 10);

        $fiftieth = User::factory()->create(['email' => 'm50@example.com', 'phone' => '+1555000050']);
        DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($fiftieth, $promo->fresh()));

        $this->assertDatabaseHas('transactions', [
            'transactable_id' => $partner->id,
            'category' => Transaction::CATEGORY_REFERRAL_MILESTONE,
            'amount' => '30.00',
        ]);

        $this->seedUsages($partner, $promo, 49, 51);
        $this->awardExistingMilestone($partner, 50);

        $hundredth = User::factory()->create(['email' => 'm100@example.com', 'phone' => '+1555000100']);
        DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($hundredth, $promo->fresh()));

        $this->assertDatabaseHas('transactions', [
            'transactable_id' => $partner->id,
            'category' => Transaction::CATEGORY_REFERRAL_MILESTONE,
            'amount' => '75.00',
        ]);
    }

    public function test_partner_earnings_page_lists_commission_and_milestone(): void
    {
        $this->fakeSuccessfulProvider(50.00);
        [$user, $partner] = $this->registerReferredUser();
        Sanctum::actingAs($user);
        $this->postJson('/api/user/esim/orders', ['package_code' => 'PHAJHEAYP'])->assertCreated();

        $commission = Transaction::query()
            ->where('transactable_id', $partner->id)
            ->where('category', Transaction::CATEGORY_PURCHASE_COMMISSION)
            ->first();
        $this->assertNotNull($commission);

        $this->actingAs($partner, 'partner');

        Livewire::test(Earnings::class)
            ->assertSee($commission->transaction_id)
            ->assertSee(__('partner.earnings.type_purchase'));
    }

    /**
     * @return array{0: User, 1: Partner}
     */
    protected function registerReferredUser(): array
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);
        $user = $this->fundedUser();

        DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($user, $promo));

        return [$user->fresh(), $partner->fresh()];
    }

    protected function makePartner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Reward Partner',
            'email' => 'reward-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }

    protected function makePromo(Partner $partner): PromoCode
    {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'REWARD10',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);
    }

    protected function seedUsages(Partner $partner, PromoCode $promo, int $count, int $start = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $n = $start + $i;
            $user = User::factory()->create([
                'email' => "seed{$n}@example.com",
                'phone' => '+1666'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            ]);

            PromoUsage::query()->create([
                'promo_code_id' => $promo->id,
                'user_id' => $user->id,
                'partner_id' => $partner->id,
                'bonus_mb_given' => 200,
                'partner_reward' => 1.50,
                'used_at' => now(),
            ]);
            $promo->increment('usage_count');
        }
    }

    protected function awardExistingMilestone(Partner $partner, int $threshold): void
    {
        Transaction::query()->create([
            'transaction_id' => 'TXN-M'.str_pad((string) $threshold, 5, '0', STR_PAD_LEFT),
            'transactable_type' => Partner::class,
            'transactable_id' => $partner->id,
            'type' => Transaction::TYPE_CREDIT,
            'category' => Transaction::CATEGORY_REFERRAL_MILESTONE,
            'amount' => $threshold === 10 ? '5.00' : '30.00',
            'balance_before' => '0.00',
            'balance_after' => '0.00',
            'currency' => 'USD',
            'status' => Transaction::STATUS_COMPLETED,
            'meta' => ['threshold' => $threshold],
        ]);
    }
}
