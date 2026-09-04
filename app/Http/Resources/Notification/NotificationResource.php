<?php

namespace App\Http\Resources\Notification;

use App\Enums\PaymentNotificationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    private const LINKS = [
        'client_import' => '/admin/clients',
    ];

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
            'title' => $this->title($kind),
            'message' => $data['message'] ?? '',
            'summary' => $data['summary'] ?? null,
            'status' => $data['status'] ?? null,
            'link' => $data['link'] ?? self::LINKS[$kind] ?? null,
            'read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'created_for_humans' => $this->created_at?->diffForHumans(),
            'project_id' => $data['project_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'invoice_id' => $data['invoice_id'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'amount' => $data['amount'] ?? null,
        ];
    }

    private function title(string $kind): string
    {
        return PaymentNotificationType::tryFrom($kind)?->label()
            ?? match ($kind) {
                'client_import' => 'Clients CSV upload',
                default => 'Notification',
            };
    }
}
