<?php

namespace App\Http\Controllers\V1\Client\Notification;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\Client;
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
        /** @var Client $client */
        $client = $request->user();

        return ApiResponse::respondWithSuccess(data: [
            'notifications' => NotificationResource::collection(
                $this->notificationService->list($client, $request->integer('limit', 15))
            ),
            'unread_count' => $this->notificationService->unreadCount($client),
        ], message: 'Notifications retrieved successfully.');
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        $this->authorizeOwnership($request, $notification);

        $this->notificationService->markAsRead($notification);

        return ApiResponse::respondSuccess('Notification marked as read.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $this->notificationService->markAllAsRead($client);

        return ApiResponse::respondSuccess('All notifications marked as read.');
    }

    public function destroy(Request $request, DatabaseNotification $notification): JsonResponse
    {
        $this->authorizeOwnership($request, $notification);

        $this->notificationService->delete($notification);

        return ApiResponse::respondSuccess('Notification deleted.');
    }

    private function authorizeOwnership(Request $request, DatabaseNotification $notification): void
    {
        $client = $request->user();

        if ($notification->notifiable_type !== $client->getMorphClass()
            || (string) $notification->notifiable_id !== (string) $client->getKey()) {
            throw new AccessDeniedHttpException('This notification belongs to another client.');
        }
    }
}
