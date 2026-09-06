<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\LoginUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\User\UserAuthService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __invoke(LoginUserRequest $request, UserAuthService $auth): JsonResponse
    {
        $result = $auth->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return ApiResponse::success(__('api.user.logged_in'), [
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => UserResource::make($result['user'])->resolve(),
        ]);
    }
}
