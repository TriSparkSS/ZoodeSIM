<?php

namespace Tests\Feature;

use App\Livewire\Partner\Notifications as PartnerNotifications;
use App\Livewire\Partner\Settings;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\Partner\PayoutProcessedNotification;
use App\Notifications\Partner\ReferralMilestoneNotification;
use App\Notifications\Partner\ReferralRegisteredNotification;
use App\Notifications\User\PromoBonusNotification;
use App\Services\Partner\Contracts\WithdrawalServiceInterface;
use App\Services\Promo\PromoRedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_promo_redemption_notifies_partner_and_user(): void
    {
        Notification::fake();

        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);
        $user = User::factory()->create();

        DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($user, $promo));

        Notification::assertSentTo($partner, ReferralRegisteredNotification::class);
        Notification::assertSentTo($user, PromoBonusNotification::class);
    }

    public function test_tenth_referral_sends_milestone_notification(): void
    {
        Notification::fake();

        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);

        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create([
                'email' => "n{$i}@example.com",
                'phone' => '+1555100'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            ]);
            DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($user, $promo->fresh()));
        }

        Notification::assertSentTo($partner, ReferralMilestoneNotification::class, function (ReferralMilestoneNotification $notification) {
            return $notification->threshold === 10;
        });
    }

    public function test_user_can_list_and_mark_notifications_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new PromoBonusNotification(
            $this->makeUsage($user),
        ));

        Sanctum::actingAs($user);

        $list = $this->getJson('/api/user/notifications')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $id = $list->json('data.notifications.0.id');
        $this->assertNotEmpty($id);

        $this->postJson("/api/user/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('data.notification.read_at', fn ($value) => $value !== null);

        $this->getJson('/api/user/notifications')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_partner_notifications_page_lists_referral_and_marks_read(): void
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);
        $user = User::factory()->create();

        DB::transaction(fn () => app(PromoRedemptionService::class)->redeem($user, $promo));

        $this->actingAs($partner, 'partner');

        $notification = $partner->fresh()->notifications()->first();
        $this->assertNotNull($notification);

        Livewire::test(PartnerNotifications::class)
            ->assertSee(__('notifications.referral.title'))
            ->call('markRead', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_payout_completion_notifies_partner(): void
    {
        Notification::fake();

        $partner = $this->makePartner(['balance' => 80, 'total_earned' => 80]);
        $withdrawal = Withdrawal::query()->create([
            'partner_id' => $partner->id,
            'amount' => 50,
            'method' => Withdrawal::METHOD_PAYPAL,
            'payout_details' => $partner->email,
            'status' => Withdrawal::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        app(WithdrawalServiceInterface::class)->complete($withdrawal, $admin);

        Notification::assertSentTo($partner->fresh(), PayoutProcessedNotification::class);
    }

    public function test_expiring_promo_command_notifies_once(): void
    {
        $partner = $this->makePartner();
        $this->makePromo($partner, expiresAt: now()->addDays(3)->setTime(12, 0));

        Artisan::call('promos:notify-expiring');
        Artisan::call('promos:notify-expiring');

        $this->assertSame(1, $partner->fresh()->notifications()
            ->where('data->type', 'promo_expiring')
            ->count());
    }

    public function test_partner_settings_persist_notification_preferences(): void
    {
        $partner = $this->makePartner();
        $this->actingAs($partner, 'partner');

        Livewire::test(Settings::class)
            ->set('emailNotifications', false)
            ->set('telegramNotifications', true)
            ->set('firstName', 'Reward')
            ->set('lastName', 'Partner')
            ->set('email', $partner->email)
            ->set('payoutMethod', Withdrawal::METHOD_PAYPAL)
            ->set('payoutDetails', $partner->email)
            ->call('save');

        $partner->refresh();
        $this->assertFalse($partner->email_notifications);
        $this->assertTrue($partner->telegram_notifications);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makePartner(array $overrides = []): Partner
    {
        return Partner::query()->create(array_merge([
            'name' => 'Notify Partner',
            'email' => 'notify-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ], $overrides));
    }

    protected function makePromo(Partner $partner, mixed $expiresAt = null): PromoCode
    {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'NOTIFY10',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => $expiresAt ?? now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);
    }

    protected function makeUsage(User $user): PromoUsage
    {
        $partner = $this->makePartner();
        $promo = $this->makePromo($partner);

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
