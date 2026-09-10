<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\IndexEsimOrderRequest;
use App\Http\Resources\Api\EsimOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Esim\Contracts\EsimOrderServiceInterface;
use Illuminate\Http\JsonResponse;

class IndexEsimOrderController extends Controller
{
    public function __invoke(
        IndexEsimOrderRequest $request,
        EsimOrderServiceInterface $orders,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $records = $orders->listOwned($user, $request->filters());

        return ApiResponse::success(__('api.esim.orders_retrieved'), [
            'orders' => EsimOrderResource::collection($records)->resolve(),
        ]);
    }
}
