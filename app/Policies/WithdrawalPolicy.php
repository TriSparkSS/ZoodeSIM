<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    public function view(Partner $partner, Withdrawal $withdrawal): bool
    {
        return $withdrawal->partner_id === $partner->id;
    }

    public function update(Partner $partner, Withdrawal $withdrawal): bool
    {
        return $withdrawal->partner_id === $partner->id;
    }
}
