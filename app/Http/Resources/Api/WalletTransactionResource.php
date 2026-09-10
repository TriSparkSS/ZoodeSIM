<?php

namespace App\Http\Resources\Api;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class WalletTransactionResource extends JsonResource
{
    /**
     * @return array{transaction_id: string, type: string, category: string, amount: string, balance_before: string, balance_after: string, description: string|null, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'type' => $this->type,
            'category' => $this->category,
            'amount' => (string) $this->amount,
            'balance_before' => (string) $this->balance_before,
            'balance_after' => (string) $this->balance_after,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
