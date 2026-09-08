<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Partner;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    public function create(Partner $partner): bool
    {
        return $partner->isActive();
    }

    public function view(Partner $partner, Withdrawal $withdrawal): bool
    {
        return $withdrawal->partner_id === $partner->id;
    }

    public function update(Partner $partner, Withdrawal $withdrawal): bool
    {
        return $withdrawal->partner_id === $partner->id
            && $withdrawal->isActionable();
    }

    public function complete(Admin $admin, Withdrawal $withdrawal): bool
    {
        return $withdrawal->isActionable();
    }

    public function reject(Admin $admin, Withdrawal $withdrawal): bool
    {
        return $withdrawal->isActionable();
    }
}
