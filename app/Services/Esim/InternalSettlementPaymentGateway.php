<?php

namespace App\Services\Esim;

use App\DataTransferObjects\EsimPaymentResult;
use App\Models\EsimOrder;
use App\Services\Esim\Contracts\EsimPaymentGatewayInterface;
use App\Support\Money;
use Illuminate\Support\Facades\Log;

class InternalSettlementPaymentGateway implements EsimPaymentGatewayInterface
{
    public function settle(EsimOrder $order): EsimPaymentResult
    {
        $amount = Money::fromDecimal(
            (string) ($order->charged_amount ?? $order->customer_price),
            (string) $order->currency,
        );

        Log::info('eSIM payment settling', [
            'order_id' => $order->id,
            'amount_cents' => $amount->cents,
            'currency' => $amount->currency,
        ]);

        return EsimPaymentResult::paid();
    }
}
