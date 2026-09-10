<?php

namespace App\Http\Controllers\Api\User;

use App\DataTransferObjects\AdjustUserBalanceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\AdjustUserWalletRequest;
use App\Http\Resources\Api\WalletTransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\User\Contracts\UserBalanceAdjustmentServiceInterface;
use Illuminate\Http\JsonResponse;

class StoreWalletController extends Controller
{
    public function __invoke(
        AdjustUserWalletRequest $request,
        UserBalanceAdjustmentServiceInterface $adjustments,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $transaction = $adjustments->adjustByUser($user, new AdjustUserBalanceData(
            direction: $request->validated('direction'),
            amount: $request->validated('amount'),
            note: (string) ($request->validated('note') ?? ''),
        ));

        $user->refresh();

        return ApiResponse::success(__('api.wallet.adjusted'), [
            'balance' => (string) $user->balance,
            'currency' => (string) $transaction->currency,
            'transaction' => WalletTransactionResource::make($transaction)->resolve(),
        ]);
    }
}
