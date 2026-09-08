<?php

namespace App\Services\Referral\Contracts;

use App\DataTransferObjects\EsimPriceQuote;
use App\DataTransferObjects\PurchaseOffer;
use App\Models\User;

interface PurchaseOfferServiceInterface
{
    public function offerFor(User $user, EsimPriceQuote $quote): PurchaseOffer;
}
