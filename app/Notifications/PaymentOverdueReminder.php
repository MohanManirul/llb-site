<?php

namespace App\Notifications;

use App\Enums\PaymentNotificationType;
use App\Models\Project;
use Illuminate\Notifications\Notification;

class PaymentOverdueReminder extends Notification
{
    public function __construct(
        private readonly Project $project,
        private readonly float $dueAmount,
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
            '%s:project:%d:%s:%s',
            PaymentNotificationType::PaymentOverdueReminder->value,
            $this->project->id,
            $this->project->next_payment_date->toDateString(),
            today()->toDateString(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $dueDate = $this->project->next_payment_date;
        $daysOverdue = (int) $dueDate->diffInDays(today());

        return [
            'type' => PaymentNotificationType::PaymentOverdueReminder->value,
            'status' => 'failed',
            'message' => sprintf(
                'Payment of %s for %s is overdue. It was due on %s.',
                formatMoney($this->dueAmount),
                $this->project->project_name,
                $dueDate->format('d M Y'),
            ),
            'summary' => $daysOverdue === 1
                ? 'Overdue by 1 day'
                : "Overdue by {$daysOverdue} days",
            'link' => "/projects/{$this->project->id}",
            'project_id' => $this->project->id,
            'client_id' => $this->project->client_id,
            'due_date' => $dueDate->toDateString(),
            'amount' => round($this->dueAmount, 2),
            'days_overdue' => $daysOverdue,
        ];
    }
}
