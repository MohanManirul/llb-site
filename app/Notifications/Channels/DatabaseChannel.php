<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;

class DatabaseChannel extends BaseDatabaseChannel
{
    /**
     * @return array<string, mixed>
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        return parent::buildPayload($notifiable, $notification) + [
            'dedupe_key' => method_exists($notification, 'dedupeKey')
                ? $notification->dedupeKey($notifiable)
                : null,
        ];
    }
}
