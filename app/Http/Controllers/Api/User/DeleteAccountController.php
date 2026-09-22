<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\User\Contracts\UserAccountDeletionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteAccountController extends Controller
{
    public function __invoke(Request $request, UserAccountDeletionServiceInterface $deletion): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $deletion->delete($user);

        return ApiResponse::success(__('api.user.account_deleted'));
    }
}
