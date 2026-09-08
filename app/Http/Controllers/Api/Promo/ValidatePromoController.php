<?php

namespace App\Http\Controllers\Api\Promo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Promo\ValidatePromoRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Promo\Contracts\PromoValidationServiceInterface;
use Illuminate\Http\JsonResponse;

class ValidatePromoController extends Controller
{
    public function __invoke(
        ValidatePromoRequest $request,
        PromoValidationServiceInterface $validation,
    ): JsonResponse {
        $result = $validation->validate(
            $request->validated('code'),
            $request->validated('email'),
        );

        $message = $result->valid
            ? __('api.promo.valid')
            : ($result->reason ?? __('api.promo.invalid'));

        return ApiResponse::success($message, $result->toArray());
    }
}
