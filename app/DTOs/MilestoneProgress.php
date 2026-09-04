<?php

namespace App\DTOs;

use App\Enums\HealthStatus;

final readonly class MilestoneProgress
{
    public function __construct(
        public ?int $milestoneId,
        public ?int $sequence,
        public ?string $label,
        public ?string $periodStart,
        public ?string $periodEnd,
        public float $target,
        public float $achieved,
        public float $progress,
        public float $rawProgress,
        public bool $started,
        public HealthStatus $health,
    ) {}

    public static function none(): self
    {
        return new self(
            milestoneId: null,
            sequence: null,
            label: null,
            periodStart: null,
            periodEnd: null,
            target: 0.0,
            achieved: 0.0,
            progress: 0.0,
            rawProgress: 0.0,
            started: false,
            health: HealthStatus::Upcoming,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->milestoneId,
            'sequence' => $this->sequence,
            'label' => $this->label,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'target' => round($this->target, 2),
            'achieved' => round($this->achieved, 2),
            'progress' => round($this->progress, 2),
            'raw_progress' => round($this->rawProgress, 2),
            'started' => $this->started,
            'health_status' => $this->health->value,
            'health_label' => $this->health->label(),
            'health_color' => $this->health->color(),
        ];
    }
}
