<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function render(Invoice $invoice, Project $project): string
    {
        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'project' => $project,
            'client' => $project->client,
        ])->setPaper('a4')->output();
    }

    public function fileName(Invoice $invoice): string
    {
        return $invoice->invoice_number.'.pdf';
    }
}
