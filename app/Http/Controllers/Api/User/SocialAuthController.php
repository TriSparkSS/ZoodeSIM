<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\SocialAuthRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\User\Contracts\UserSocialAuthServiceInterface;
use Illuminate\Http\JsonResponse;

class SocialAuthController extends Controller
{
    public function __invoke(
        SocialAuthRequest $request,
        UserSocialAuthServiceInterface $social,
    ): JsonResponse {
        $result = $social->authenticate(
            idToken: $request->validated('id_token'),
            provider: $request->validated('provider'),
            name: $request->validated('name'),
            referralCode: $request->validated('referral_code'),
            deviceId: $request->validated('device_id'),
            ip: $request->ip(),
        );

        $status = $result['is_new_user'] ? 201 : 200;
        $message = $result['is_new_user']
            ? __('api.user.registered')
            : __('api.user.logged_in');

        return ApiResponse::success($message, [
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'is_new_user' => $result['is_new_user'],
            'user' => UserResource::make($result['user'])->resolve(),
            'bonus_type' => $result['bonus_type'],
            'bonus_amount' => $result['bonus_amount'],
            'bonus_mb' => $result['bonus_mb'],
        ], $status);
    }
}
