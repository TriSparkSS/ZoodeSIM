<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\CreateEsimOrderRequest;
use App\Http\Resources\Api\EsimOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Esim\Contracts\EsimOrderServiceInterface;
use Illuminate\Http\JsonResponse;

class StoreEsimOrderController extends Controller
{
    public function __invoke(
        CreateEsimOrderRequest $request,
        EsimOrderServiceInterface $orders,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $order = $orders->purchase(
            $user,
            $request->validated('package_code'),
            $request->idempotencyKey(),
        );

        return ApiResponse::success(__('api.esim.purchased'), EsimOrderResource::make($order)->resolve(), 201);
    }
}
