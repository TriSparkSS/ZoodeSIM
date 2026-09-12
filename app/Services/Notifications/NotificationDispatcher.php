<?php

namespace App\Services\Notifications;

use App\Models\EsimOrder;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\Partner\PayoutProcessedNotification;
use App\Notifications\Partner\PromoExpiringNotification;
use App\Notifications\Partner\PromoUnlockedNotification;
use App\Notifications\Partner\PurchaseCommissionNotification;
use App\Notifications\Partner\ReferralMilestoneNotification;
use App\Notifications\Partner\ReferralRegisteredNotification;
use App\Notifications\User\PromoBonusNotification;
use App\Notifications\User\PurchaseCashbackNotification;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Support\Money;

class NotificationDispatcher implements NotificationDispatcherInterface
{
    public function referralRegistered(Partner $partner, User $user, PromoUsage $usage): void
    {
        $partner->notify(new ReferralRegisteredNotification($user, $usage));
        $user->notify(new PromoBonusNotification($usage));
    }

    public function purchaseCommission(Partner $partner, User $user, EsimOrder $order, Money $amount): void
    {
        $partner->notify(new PurchaseCommissionNotification($user, $order, $amount));
    }

    public function purchaseCashback(User $user, EsimOrder $order, Money $amount): void
    {
        $user->notify(new PurchaseCashbackNotification($order, $amount));
    }

    public function milestoneReached(Partner $partner, int $threshold, Money $amount): void
    {
        $partner->notify(new ReferralMilestoneNotification($threshold, $amount));
    }

    public function payoutProcessed(Partner $partner, Withdrawal $withdrawal): void
    {
        $partner->notify(new PayoutProcessedNotification($withdrawal));
    }

    public function promoExpiring(Partner $partner, PromoCode $promo): void
    {
        $alreadySent = $partner->notifications()
            ->where('data->type', 'promo_expiring')
            ->where('data->promo_code_id', $promo->id)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $partner->notify(new PromoExpiringNotification($promo));
    }

    public function promoUnlocked(Partner $partner, PromoCode $promo): void
    {
        $partner->notify(new PromoUnlockedNotification($promo));
    }
}
