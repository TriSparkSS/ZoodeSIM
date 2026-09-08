<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EsimOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Esim\Contracts\EsimOrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowEsimOrderController extends Controller
{
    public function __invoke(
        Request $request,
        string $order,
        EsimOrderServiceInterface $orders,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $record = $orders->findOwned($user, $order);

        return ApiResponse::success(__('api.esim.order_retrieved'), EsimOrderResource::make($record)->resolve());
    }
}
