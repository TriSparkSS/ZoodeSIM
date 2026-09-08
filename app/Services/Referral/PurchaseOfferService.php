<?php

namespace App\Services\Referral;

use App\DataTransferObjects\EsimPriceQuote;
use App\DataTransferObjects\PurchaseOffer;
use App\Models\EsimOrder;
use App\Models\User;
use App\Services\Referral\Contracts\PurchaseOfferServiceInterface;
use App\Support\Money;

class PurchaseOfferService implements PurchaseOfferServiceInterface
{
    public function __construct(
        protected ReferralProgramSettings $settings,
    ) {}

    public function offerFor(User $user, EsimPriceQuote $quote): PurchaseOffer
    {
        $list = $quote->customerPrice;

        if (! $this->qualifiesForFirstPurchaseDiscount($user)) {
            return new PurchaseOffer(
                listPrice: $list,
                discountAmount: Money::fromCents(0, $list->currency),
                discountPercentage: '0.00',
                chargedAmount: $list,
                discountApplied: false,
            );
        }

        $percent = $this->settings->firstPurchaseDiscountPercent();
        $discount = $list->percentageOf($percent);

        return new PurchaseOffer(
            listPrice: $list,
            discountAmount: $discount,
            discountPercentage: $percent,
            chargedAmount: $list->subtract($discount),
            discountApplied: $discount->cents > 0,
        );
    }

    protected function qualifiesForFirstPurchaseDiscount(User $user): bool
    {
        $days = $this->settings->firstPurchaseDiscountDays();

        if ($days < 1 || $user->created_at === null || $user->created_at->lt(now()->subDays($days))) {
            return false;
        }

        return ! EsimOrder::query()
            ->where('user_id', $user->id)
            ->where('payment_status', EsimOrder::PAYMENT_PAID)
            ->exists();
    }
}
