<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Project;

class InvoiceService
{
    public function __construct(
        private ProjectAmountService $amountService,
    ) {}

    public function generateInvoice(int $projectId): Invoice
    {
        $project = Project::findOrFail($projectId);
        $amounts = $this->amountService->forProjectModel($project);

        $invoiceNumber = $this->generateInvoiceNumber($projectId);

        return Invoice::create([
            'project_id' => $projectId,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now()->toDateString(),
            'total_amount' => $amounts['total'],
            'paid_amount' => $amounts['paid'],
            'due_amount' => $amounts['due'],
            'due_date' => $project->next_payment_date ?? now()->addDays(30)->toDateString(),
        ]);
    }

    public function generateInvoiceNumber(int $projectId): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $sequence = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return "INV-{$year}-{$month}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
