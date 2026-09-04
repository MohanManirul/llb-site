<?php

namespace App\Jobs;

use App\Mail\SendInvoiceMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function handle(): void
    {
        $invoice = Invoice::with('project.client')->find($this->invoice->id);

        if (! $invoice || $invoice->email_sent_at) {
            return;
        }

        $project = $invoice->project;

        if (! $project->client?->email) {
            return;
        }

        Mail::to($project->client->email)
            ->send(new SendInvoiceMail($invoice, $project));

        $invoice->update([
            'email_sent_to' => $project->client->email,
            'email_sent_at' => now(),
        ]);
    }
}
