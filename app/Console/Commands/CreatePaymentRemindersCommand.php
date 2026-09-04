<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Payment\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CreatePaymentRemindersCommand extends Command
{
    protected $signature = 'payment:create-reminders';

    protected $description = 'Notify clients daily about upcoming and overdue payments';

    public function __construct(
        private NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $window = today()->addDays((int) config('payment.notification_days_before', 7));

        $due = $this->notify(
            fn (Builder $query) => $query->whereBetween('next_payment_date', [today(), $window]),
            fn (Project $project, float $amount): bool => $this->notificationService->dueReminder($project, $amount),
        );

        $overdue = $this->notify(
            fn (Builder $query) => $query->whereDate('next_payment_date', '<', today()),
            fn (Project $project, float $amount): bool => $this->notificationService->overdueReminder($project, $amount),
        );

        $this->info("Reminders sent — upcoming: {$due}, overdue: {$overdue}");

        return self::SUCCESS;
    }

    private function notify(callable $scope, callable $send): int
    {
        $sent = 0;

        Project::query()
            ->whereNotNull('next_payment_date')
            ->whereNotNull('client_id')
            ->whereHas('client')
            ->tap($scope)
            ->withSum('payments as payments_sum', 'amount')
            ->chunkById(200, function ($projects) use ($send, &$sent) {
                foreach ($projects as $project) {
                    $total = (float) ($project->total_amount ?? $project->package_amount ?? 0);
                    $amount = $total - (float) ($project->payments_sum ?? 0);

                    if ($amount <= 0.0) {
                        continue;
                    }

                    if ($send($project, $amount)) {
                        $sent++;
                        $this->line("  {$project->project_name} — ".formatMoney($amount));
                    }
                }
            });

        return $sent;
    }
}
