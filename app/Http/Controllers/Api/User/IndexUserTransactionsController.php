<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\IndexUserTransactionsRequest;
use App\Http\Resources\Api\WalletTransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Wallet\TransactionQueryService;
use Illuminate\Http\JsonResponse;

class IndexUserTransactionsController extends Controller
{
    public function __invoke(
        IndexUserTransactionsRequest $request,
        TransactionQueryService $transactions,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $page = $transactions->paginateForUser($user, $request->perPage());

        return ApiResponse::success(__('api.wallet.transactions_retrieved'), [
            'transactions' => WalletTransactionResource::collection($page->items())->resolve(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
        ]);
    }
}
