<?php

namespace App\Services\Notification;

use App\Models\Client;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final class NotificationService
{
    public function list(User|Client $user, int $limit = 15): Collection
    {
        return $user->notifications()->latest()->limit($limit)->get();
    }

    public function unreadCount(User|Client $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(User|Client $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function delete(DatabaseNotification $notification): void
    {
        $notification->delete();
    }
}
