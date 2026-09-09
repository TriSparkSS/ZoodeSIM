<?php

namespace App\Http\Resources\Api;

use App\Models\EsimOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EsimOrder
 */
class EsimOrderResource extends JsonResource
{
    /**
     * @return array{order: array<string, mixed>, esim: array<string, mixed>|null}
     */
    public function toArray(Request $request): array
    {
        $detail = $this->detail;

        return [
            'order' => [
                'id' => $this->id,
                'package_code' => $this->package_code,
                'package_name' => $this->package_name,
                'location' => $this->package_location,
                'data_volume' => $this->package_data_volume,
                'duration' => $this->package_duration,
                'price' => (float) ($this->charged_amount ?? $this->customer_price),
                'list_price' => (float) $this->customer_price,
                'discount_amount' => (float) ($this->discount_amount ?? 0),
                'discount_percentage' => (float) ($this->discount_percentage ?? 0),
                'currency' => $this->currency,
                'status' => $this->order_status,
            ],
            'esim' => $detail === null ? null : [
                'service_id' => $this->publicProviderId($detail->service_id),
                'iccid' => $detail->iccid,
                'qr_code_url' => $detail->qr_code_url,
                'activation_url' => $detail->activation_url,
                'status' => $detail->esim_status,
            ],
        ];
    }

    protected function publicProviderId(mixed $id): int|string|null
    {
        if ($id === null || $id === '') {
            return $id;
        }

        $id = (string) $id;

        return ctype_digit($id) ? (int) $id : $id;
    }
}
