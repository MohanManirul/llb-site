<?php

namespace App\Services\Payment;

use App\Jobs\SendInvoiceEmailJob;
use App\Models\Payment;
use App\Models\Project;
use App\Utilities\JsonSchemaValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private PaymentHistoryService $historyService,
        private InvoiceService $invoiceService,
        private NotificationService $notificationService,
        private ProjectAmountService $amountService,
    ) {}

    public function createPayment(
        int $projectId,
        float $amount,
        string $paymentType,
        array $details,
        ?string $proofPath,
        int $userId,
        ?string $nextPaymentDate = null,
    ): Payment {
        return DB::transaction(function () use (
            $projectId,
            $amount,
            $paymentType,
            $details,
            $proofPath,
            $userId,
            $nextPaymentDate,
        ) {
            Log::info('Payment attempt started', [
                'project_id' => $projectId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'user_id' => $userId,
                'ip_address' => request()->ip(),
            ]);

            try {
                $gateway = PaymentGatewayFactory::resolve($paymentType);
                $response = $gateway->charge($amount, $details);

                JsonSchemaValidator::validateGatewayResponse($response->metadata ?? []);
                $sanitizedResponse = JsonSchemaValidator::sanitizeGatewayResponse($response->metadata ?? []);

                $payment = Payment::create([
                    'project_id' => $projectId,
                    'payment_type' => $paymentType,
                    'amount' => $amount,
                    'payment_date' => $details['payment_date'] ?? now()->toDateString(),
                    'reference_number' => $details['reference_number'] ?? null,
                    'proof' => $proofPath,
                    'gateway_response' => $sanitizedResponse,
                    'notes' => $details['notes'] ?? null,
                    'created_by' => $userId,
                ]);

                Log::info('Payment created successfully', [
                    'payment_id' => $payment->id,
                    'project_id' => $projectId,
                    'amount' => $amount,
                    'user_id' => $userId,
                ]);

                $project = $payment->project;
                $oldPaidAmount = $this->calculateProjectAmounts($projectId)['paid'];

                $this->historyService->logPaymentCreated(
                    $payment,
                    $project,
                    $oldPaidAmount,
                    $userId,
                );

                $this->updateProjectAmounts($project, $nextPaymentDate);

                DB::afterCommit(function () use ($payment) {
                    $invoice = $this->invoiceService->generateInvoice($payment->project_id);

                    $this->notificationService->paymentReceived($payment, $invoice);

                    SendInvoiceEmailJob::dispatch($invoice);
                });

                return $payment->load('createdBy');
            } catch (\Exception $e) {
                Log::warning('Payment creation failed', [
                    'project_id' => $projectId,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'ip_address' => request()->ip(),
                ]);

                throw $e;
            }
        });
    }

    public function calculateProjectAmounts(int $projectId): array
    {
        return $this->amountService->forProject($projectId);
    }

    public function getProjectPaymentStatus(int $projectId): array
    {
        $project = Project::with([
            'payments' => fn ($q) => $q->select('project_id', 'amount')->latest(),
            'invoices' => fn ($q) => $q->latest()->limit(1),
        ])->findOrFail($projectId);

        $amounts = $this->calculateProjectAmounts($projectId);

        return [
            'total_amount' => $amounts['total'],
            'paid_amount' => $amounts['paid'],
            'due_amount' => $amounts['due'],
            'next_payment_date' => $project->next_payment_date?->toDateString(),
            'last_payment_date' => $project->last_payment_date?->toDateString(),
            'payment_count' => $project->payments->count(),
        ];
    }

    private function updateProjectAmounts(Project $project, ?string $nextPaymentDate = null): void
    {
        $amounts = $this->calculateProjectAmounts($project->id);

        $project->update([
            'amount_paid' => $amounts['paid'],
            'amount_due' => $amounts['due'],
            'next_payment_date' => $nextPaymentDate,
            'last_payment_date' => now()->toDateString(),
        ]);
    }
}
