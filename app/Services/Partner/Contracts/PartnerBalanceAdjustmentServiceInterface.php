<?php

namespace App\Services\Partner\Contracts;

use App\DataTransferObjects\AdjustPartnerBalanceData;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\Transaction;

interface PartnerBalanceAdjustmentServiceInterface
{
    public function adjust(Partner $partner, Admin $admin, AdjustPartnerBalanceData $data): Transaction;
}
