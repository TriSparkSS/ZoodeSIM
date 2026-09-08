<?php

namespace App\Services\Partner\Contracts;

use App\DataTransferObjects\CreateWithdrawalRequestData;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\Withdrawal;

interface WithdrawalServiceInterface
{
    public function request(Partner $partner, CreateWithdrawalRequestData $data): Withdrawal;

    public function complete(Withdrawal $withdrawal, Admin $admin): Withdrawal;

    public function reject(Withdrawal $withdrawal, Admin $admin, ?string $note = null): Withdrawal;

    public function minimumAmount(): string;
}
