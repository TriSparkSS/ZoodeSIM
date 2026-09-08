<?php

namespace App\Services\Notifications\Contracts;

use App\Models\EsimOrder;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Money;

interface NotificationDispatcherInterface
{
    public function referralRegistered(Partner $partner, User $user, PromoUsage $usage): void;

    public function purchaseCommission(Partner $partner, User $user, EsimOrder $order, Money $amount): void;

    public function purchaseCashback(User $user, EsimOrder $order, Money $amount): void;

    public function milestoneReached(Partner $partner, int $threshold, Money $amount): void;

    public function payoutProcessed(Partner $partner, Withdrawal $withdrawal): void;

    public function promoExpiring(Partner $partner, PromoCode $promo): void;
}
