<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $user->notifications()->latest()->limit(50)->get();

        return ApiResponse::success(__('api.notifications.retrieved'), [
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => NotificationResource::collection($notifications)->resolve(),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $item = $user->notifications()->whereKey($notification)->first();

        if ($item === null) {
            return ApiResponse::error(__('api.notifications.not_found'), [], 404);
        }

        /** @var DatabaseNotification $item */
        $item->markAsRead();

        return ApiResponse::success(__('api.notifications.marked_read'), [
            'notification' => NotificationResource::make($item->fresh())->resolve(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        return ApiResponse::success(__('api.notifications.all_marked_read'), [
            'unread_count' => 0,
        ]);
    }
}
