<?php

namespace App\Notifications;

use App\Enums\PaymentNotificationType;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    public function __construct(
        private readonly Payment $payment,
        private readonly ?Invoice $invoice = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function dedupeKey(object $notifiable): string
    {
        return sprintf(
            '%s:payment:%d',
            PaymentNotificationType::PaymentReceivedConfirmation->value,
            $this->payment->id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $project = $this->payment->project;

        return [
            'type' => PaymentNotificationType::PaymentReceivedConfirmation->value,
            'status' => 'success',
            'message' => sprintf(
                'Payment of %s received for %s.',
                formatMoney($this->payment->amount),
                $project->project_name,
            ),
            'summary' => sprintf(
                '%s · %s',
                $this->payment->payment_type->label(),
                $this->payment->payment_date->format('d M Y'),
            ),
            'link' => "/projects/{$project->id}",
            'project_id' => $project->id,
            'client_id' => $project->client_id,
            'payment_id' => $this->payment->id,
            'amount' => round((float) $this->payment->amount, 2),
            'invoice_id' => $this->invoice?->id,
            'invoice_number' => $this->invoice?->invoice_number,
        ];
    }
}
