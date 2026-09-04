<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentHistory;
use App\Models\Project;
use Illuminate\Pagination\Paginator;

class PaymentHistoryService
{
    public function __construct(
        private ProjectAmountService $amountService,
    ) {}

    public function logPaymentCreated(Payment $payment, Project $project, float $oldPaidAmount, int $userId): void
    {
        $newPaidAmount = $this->amountService->forProjectModel($project)['paid'];

        PaymentHistory::create([
            'project_id' => $project->id,
            'payment_id' => $payment->id,
            'action' => 'payment_created',
            'changed_amount' => $payment->amount,
            'old_paid_amount' => $oldPaidAmount,
            'new_paid_amount' => $newPaidAmount,
            'changed_by' => $userId,
        ]);
    }

    public function logGatewayResponse(Payment $payment, array $gatewayResponse, int $userId): void
    {
        PaymentHistory::create([
            'project_id' => $payment->project_id,
            'payment_id' => $payment->id,
            'action' => 'gateway_response_received',
            'changed_by' => $userId,
        ]);
    }

    public function logAmountUpdated(Payment $payment, float $oldAmount, float $newAmount, int $userId): void
    {
        PaymentHistory::create([
            'project_id' => $payment->project_id,
            'payment_id' => $payment->id,
            'action' => 'amount_updated',
            'changed_amount' => $newAmount - $oldAmount,
            'changed_by' => $userId,
        ]);
    }

    public function logInvoiceEmailed(int $projectId, int $userId): void
    {
        PaymentHistory::create([
            'project_id' => $projectId,
            'action' => 'invoice_emailed',
            'changed_by' => $userId,
        ]);
    }

    public function getProjectHistory(int $projectId, int $perPage = 15): Paginator
    {
        return PaymentHistory::where('project_id', $projectId)
            ->with(['payment', 'changedBy'])
            ->latest()
            ->simplePaginate($perPage);
    }
}
