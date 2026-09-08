<?php

namespace Tests\Feature;

use App\DataTransferObjects\CreateWithdrawalRequestData;
use App\Livewire\Admin\PromoAuditLogs;
use App\Livewire\Admin\PromoCodes;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\PromoAuditLog;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Partner\Contracts\WithdrawalServiceInterface;
use App\Services\Promo\Contracts\PromoValidationServiceInterface;
use App\Services\Promo\PromoRedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PromoAuditAndPayoutIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_and_toggling_a_promo_writes_audit_rows(): void
    {
        $partner = $this->makePartner();

        Livewire::test(PromoCodes::class)
            ->call('openCreateModal', $partner->id)
            ->set('formCode', 'AUDIT99')
            ->set('formBonusMb', '200')
            ->set('formPartnerReward', '1.50')
            ->set('formExpiresAt', now()->addDays(14)->format('Y-m-d'))
            ->call('createPromo')
            ->assertHasNoErrors();

        $promo = PromoCode::query()->where('code', 'AUDIT99')->first();
        $this->assertNotNull($promo);
        $this->assertDatabaseHas('promo_audit_logs', [
            'promo_code_id' => $promo->id,
            'action' => PromoAuditLog::ACTION_CREATED,
            'code' => 'AUDIT99',
        ]);

        Livewire::test(PromoCodes::class)->call('deactivate', $promo->id);
        $this->assertDatabaseHas('promo_audit_logs', [
            'promo_code_id' => $promo->id,
            'action' => PromoAuditLog::ACTION_DEACTIVATED,
        ]);

        Livewire::test(PromoCodes::class)->call('activate', $promo->id);
        $this->assertDatabaseHas('promo_audit_logs', [
            'promo_code_id' => $promo->id,
            'action' => PromoAuditLog::ACTION_ACTIVATED,
        ]);
    }

    public function test_failed_validate_and_successful_redeem_are_audited(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);
        $user = User::factory()->create(['name' => 'Referral User']);

        app(PromoValidationServiceInterface::class)->validate('MISSING1');

        $this->assertDatabaseHas('promo_audit_logs', [
            'action' => PromoAuditLog::ACTION_VALIDATE_FAILED,
            'code' => 'MISSING1',
        ]);

        app(PromoRedemptionService::class)->redeem($user, $promo);

        $this->assertDatabaseHas('promo_audit_logs', [
            'promo_code_id' => $promo->id,
            'action' => PromoAuditLog::ACTION_REDEEMED,
            'code' => $promo->code,
        ]);
    }

    public function test_admin_can_view_promo_audit_log(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Audit Admin',
            'email' => 'audit-admin@zoodesim.test',
            'password' => 'password123',
        ]);
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);

        PromoAuditLog::query()->create([
            'promo_code_id' => $promo->id,
            'partner_id' => $partner->id,
            'action' => PromoAuditLog::ACTION_CREATED,
            'code' => $promo->code,
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(PromoAuditLogs::class)
            ->assertSee($promo->code)
            ->assertSee($partner->name)
            ->assertSee(__('admin.promo_audit.actions.created'));
    }

    public function test_withdrawal_is_blocked_when_referral_email_matches_partner(): void
    {
        $partner = $this->makePartner(balance: '120.00');
        $promo = $this->makePromo($partner);
        $user = User::factory()->create([
            'email' => $partner->email,
            'phone' => '+19998887777',
        ]);
        $this->recordUsage($partner, $promo, $user);

        $this->expectException(ValidationException::class);

        app(WithdrawalServiceInterface::class)->request($partner, new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: 'card-1111',
        ));
    }

    public function test_withdrawal_is_blocked_when_payout_details_match_referral_phone(): void
    {
        $partner = $this->makePartner(balance: '120.00');
        $promo = $this->makePromo($partner);
        $user = User::factory()->create([
            'email' => 'referral-user@example.com',
            'phone' => '+1 (555) 010-9988',
        ]);
        $this->recordUsage($partner, $promo, $user);

        $this->expectException(ValidationException::class);

        app(WithdrawalServiceInterface::class)->request($partner, new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: '15550109988',
        ));
    }

    public function test_withdrawal_is_blocked_when_partner_user_shares_device_with_referral(): void
    {
        $partner = $this->makePartner(balance: '120.00');
        $promo = $this->makePromo($partner);

        User::factory()->create([
            'email' => $partner->email,
            'device_id' => 'shared-device-abc',
            'phone' => '+12220000001',
        ]);
        $referral = User::factory()->create([
            'email' => 'other-referral@example.com',
            'device_id' => 'shared-device-abc',
            'phone' => '+12220000002',
        ]);
        $this->recordUsage($partner, $promo, $referral);

        $this->expectException(ValidationException::class);

        app(WithdrawalServiceInterface::class)->request($partner, new CreateWithdrawalRequestData(
            amount: '50.00',
            method: Withdrawal::METHOD_CARD,
            payoutDetails: 'wallet-xyz',
        ));
    }

    public function test_admin_cannot_complete_payout_when_identity_matches(): void
    {
        $partner = $this->makePartner(balance: '120.00');
        $admin = Admin::query()->create([
            'name' => 'Payout Admin',
            'email' => 'identity-admin@zoodesim.test',
            'password' => 'password123',
        ]);

        $withdrawal = Withdrawal::query()->create([
            'partner_id' => $partner->id,
            'amount' => '60.00',
            'method' => Withdrawal::METHOD_CARD,
            'payout_details' => 'card-2222',
            'status' => Withdrawal::STATUS_PROCESSING,
            'requested_at' => now(),
        ]);

        $promo = $this->makePromo($partner);
        $this->recordUsage($partner, $promo, User::factory()->create([
            'email' => $partner->email,
        ]));

        $this->expectException(ValidationException::class);

        app(WithdrawalServiceInterface::class)->complete($withdrawal, $admin);
    }

    protected function makePartner(string $balance = '0.00'): Partner
    {
        return Partner::query()->create([
            'name' => 'Audit Partner',
            'email' => 'audit-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => $balance,
            'total_earned' => $balance,
        ]);
    }

    protected function makePromo(Partner $partner): PromoCode
    {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'AUDIT10',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);
    }

    protected function recordUsage(Partner $partner, PromoCode $promo, User $user): PromoUsage
    {
        return PromoUsage::query()->create([
            'promo_code_id' => $promo->id,
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'bonus_mb_given' => 200,
            'partner_reward' => 1.50,
            'used_at' => now(),
        ]);
    }
}
