<?php

namespace App\Notifications;

use App\Enums\PaymentNotificationType;
use App\Models\Project;
use Illuminate\Notifications\Notification;

class PaymentDueReminder extends Notification
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
            '%s:project:%d:%s',
            PaymentNotificationType::PaymentDueReminder->value,
            $this->project->id,
            $this->project->next_payment_date->toDateString(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $dueDate = $this->project->next_payment_date;
        $daysLeft = (int) today()->diffInDays($dueDate, false);

        return [
            'type' => PaymentNotificationType::PaymentDueReminder->value,
            'status' => $daysLeft <= 0 ? 'failed' : 'pending',
            'message' => sprintf(
                'Payment of %s for %s is due on %s.',
                formatMoney($this->dueAmount),
                $this->project->project_name,
                $dueDate->format('d M Y'),
            ),
            'summary' => match (true) {
                $daysLeft <= 0 => 'Due today',
                $daysLeft === 1 => 'Due tomorrow',
                default => "Due in {$daysLeft} days",
            },
            'link' => "/projects/{$this->project->id}",
            'project_id' => $this->project->id,
            'client_id' => $this->project->client_id,
            'due_date' => $dueDate->toDateString(),
            'amount' => round($this->dueAmount, 2),
        ];
    }
}
