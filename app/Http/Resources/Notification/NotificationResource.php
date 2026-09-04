<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];
        $kind = $data['type'] ?? 'general';

        return [
            'id' => $this->id,
            'kind' => $kind,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'summary' => $data['summary'] ?? null,
            'status' => $data['status'] ?? null,
            'link' => $data['link'] ?? null,
            'read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'created_for_humans' => $this->created_at?->diffForHumans(),
        ];
    }
}
