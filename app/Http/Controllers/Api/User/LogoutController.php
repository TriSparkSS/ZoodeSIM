<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\User\UserAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request, UserAuthService $auth): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $auth->logout($user);

        return ApiResponse::success(__('api.user.logged_out'));
    }
}
