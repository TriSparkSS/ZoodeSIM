<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\WalletTransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowWalletController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $currency = (string) config('pricing.currency', 'USD');

        $transactions = $user->transactions()
            ->where('currency', '!=', 'MB')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return ApiResponse::success(__('api.wallet.retrieved'), [
            'balance' => (string) $user->balance,
            'currency' => $currency,
            'transactions' => WalletTransactionResource::collection($transactions)->resolve(),
        ]);
    }
}
