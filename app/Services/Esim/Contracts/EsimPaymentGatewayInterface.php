<?php

namespace App\Services\Esim\Contracts;

use App\DataTransferObjects\EsimPaymentResult;
use App\Models\EsimOrder;

interface EsimPaymentGatewayInterface
{
    public function settle(EsimOrder $order): EsimPaymentResult;
}
