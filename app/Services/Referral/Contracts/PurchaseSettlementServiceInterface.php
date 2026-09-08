<?php

namespace App\Services\Referral\Contracts;

use App\Models\EsimOrder;

interface PurchaseSettlementServiceInterface
{
    public function settle(EsimOrder $order): void;
}
