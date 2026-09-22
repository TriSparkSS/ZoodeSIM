<?php

namespace App\Http\Controllers\Api\Promo;

use App\DataTransferObjects\PromoValidationResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Promo\ValidatePromoRequest;
use App\Http\Responses\ApiResponse;
use App\Models\PromoCode;
use App\Services\Promo\Contracts\PromoValidationServiceInterface;
use App\Services\Promo\PromoCodeGenerator;
use App\Services\Referral\Contracts\UserReferralServiceInterface;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ValidatePromoController extends Controller
{
    public function __invoke(
        ValidatePromoRequest $request,
        PromoValidationServiceInterface $validation,
        UserReferralServiceInterface $userReferrals,
        PromoCodeGenerator $codes,
    ): JsonResponse {
        $code = (string) $request->validated('code');
        $email = $request->validated('email');

        try {
            $normalized = $codes->normalize($code);
        } catch (InvalidArgumentException) {
            $result = PromoValidationResult::invalid(__('api.promo.invalid'));

            return $this->payload($result);
        }

        $result = PromoCode::query()->where('code', $normalized)->exists()
            ? $validation->validate($code, $email)
            : $userReferrals->validate($code, $email);

        return $this->payload($result);
    }

    protected function payload(PromoValidationResult $result): JsonResponse
    {
        $message = $result->valid
            ? __('api.promo.valid')
            : ($result->reason ?? __('api.promo.invalid'));

        return ApiResponse::success($message, $result->toArray());
    }
}
