<?php

namespace App\Services\Payment;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Notifications\PaymentDueReminder;
use App\Notifications\PaymentOverdueReminder;
use App\Notifications\PaymentReceived;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Notifications\Notification;

class NotificationService
{
    public function paymentReceived(Payment $payment, ?Invoice $invoice = null): bool
    {
        return $this->send(
            $payment->project,
            new PaymentReceived($payment, $invoice),
        );
    }

    public function dueReminder(Project $project, float $dueAmount): bool
    {
        if (! $project->next_payment_date) {
            return false;
        }

        return $this->send($project, new PaymentDueReminder($project, $dueAmount));
    }

    public function overdueReminder(Project $project, float $dueAmount): bool
    {
        if (! $project->next_payment_date) {
            return false;
        }

        return $this->send($project, new PaymentOverdueReminder($project, $dueAmount));
    }

    private function send(Project $project, Notification $notification): bool
    {
        $client = Client::find($project->client_id);

        if (! $client) {
            return false;
        }

        try {
            $client->notify($notification);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
