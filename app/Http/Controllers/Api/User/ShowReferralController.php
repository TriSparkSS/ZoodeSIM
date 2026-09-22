<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Referral\Contracts\UserReferralServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowReferralController extends Controller
{
    public function __invoke(
        Request $request,
        UserReferralServiceInterface $referrals,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success(__('api.user.referral'), $referrals->summary($user));
    }
}
