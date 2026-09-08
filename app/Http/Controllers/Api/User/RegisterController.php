<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\RegisterUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\User\UserAuthService;
use App\Services\User\UserRegistrationService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(
        RegisterUserRequest $request,
        UserRegistrationService $registration,
        UserAuthService $auth,
    ): JsonResponse {
        $result = $registration->register(
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            password: $request->validated('password'),
            referralCode: $request->validated('referral_code'),
            deviceId: $request->validated('device_id'),
            ip: $request->ip(),
        );

        return ApiResponse::success(__('api.user.registered'), [
            'token' => $auth->issueToken($result['user']),
            'token_type' => 'Bearer',
            'user' => UserResource::make($result['user'])->resolve(),
            'bonus_mb' => $result['bonus_mb'],
        ], 201);
    }
}
