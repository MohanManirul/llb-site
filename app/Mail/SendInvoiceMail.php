<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Project;
use App\Services\Payment\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public Project $project,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice #{$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'project' => $this->project,
                'url' => route('client.projects.show', $this->project->id),
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = app(InvoicePdfService::class);

        return [
            Attachment::fromData(
                fn (): string => $pdf->render($this->invoice, $this->project),
                $pdf->fileName($this->invoice),
            )->withMime('application/pdf'),
        ];
    }
}
