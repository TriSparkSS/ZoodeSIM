<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\PromoCode;

class PromoCodePolicy
{
    public function view(Partner $partner, PromoCode $promoCode): bool
    {
        return $promoCode->partner_id === $partner->id;
    }

    public function update(Partner $partner, PromoCode $promoCode): bool
    {
        return $promoCode->partner_id === $partner->id;
    }
}
