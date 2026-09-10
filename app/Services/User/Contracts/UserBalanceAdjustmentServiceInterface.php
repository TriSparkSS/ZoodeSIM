<?php

namespace App\Services\User\Contracts;

use App\DataTransferObjects\AdjustUserBalanceData;
use App\Models\Admin;
use App\Models\Transaction;
use App\Models\User;

interface UserBalanceAdjustmentServiceInterface
{
    public function adjustByAdmin(User $user, Admin $admin, AdjustUserBalanceData $data): Transaction;

    public function adjustByUser(User $user, AdjustUserBalanceData $data): Transaction;
}
