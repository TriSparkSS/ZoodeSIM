<?php

namespace App\Http\Controllers\Api\User;

use App\DataTransferObjects\UpdateUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UpdateUserProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\User\Contracts\UserProfileServiceInterface;
use Illuminate\Http\JsonResponse;

class UpdateProfileController extends Controller
{
    public function __invoke(
        UpdateUserProfileRequest $request,
        UserProfileServiceInterface $profiles,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $user = $profiles->update(
            $user,
            new UpdateUserData(
                name: $request->validated('name'),
                email: $request->validated('email'),
                phone: $request->validated('phone'),
                password: $request->validated('password'),
            ),
            $request->validated('current_password'),
        );

        return ApiResponse::success(__('api.user.profile_updated'), [
            ...UserResource::make($user)->resolve(),
            'bonus_mb' => (int) $user->bonus_mb,
        ]);
    }
}
