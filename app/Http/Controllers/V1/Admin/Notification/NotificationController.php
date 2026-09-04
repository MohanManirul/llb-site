<?php

namespace App\Http\Controllers\V1\Admin\Notification;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $limit = (int) $request->integer('limit', 15);

        return ApiResponse::respondWithSuccess(data: [
            'notifications' => NotificationResource::collection(
                $this->notificationService->list($user, $limit)
            ),
            'unread_count' => $this->notificationService->unreadCount($user),
        ], message: 'Notifications retrieved successfully.');
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        $this->authorizeOwnership($request, $notification);

        activity()->performedOn($notification)->log('Notification marked as read.');

        $this->notificationService->markAsRead($notification);

        return ApiResponse::respondSuccess('Notification marked as read.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->notificationService->markAllAsRead($user);

        return ApiResponse::respondSuccess('All notifications marked as read.');
    }

    public function destroy(Request $request, DatabaseNotification $notification): JsonResponse
    {
        $this->authorizeOwnership($request, $notification);

        activity()->performedOn($notification)->log('Notification deleted.');

        $this->notificationService->delete($notification);

        return ApiResponse::respondSuccess('Notification deleted.');
    }

    private function authorizeOwnership(Request $request, DatabaseNotification $notification): void
    {
        $user = $request->user();

        if ($notification->notifiable_type !== $user->getMorphClass()
            || (string) $notification->notifiable_id !== (string) $user->getKey()) {
            throw new AccessDeniedHttpException('This notification belongs to another user.');
        }
    }
}
