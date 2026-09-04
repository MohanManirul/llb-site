<?php

namespace App\Enums;

enum AssignmentReason: string
{
    case Initial = 'initial';
    case Manual = 'manual';
    case MilestoneFailure = 'milestone_failure';
    case Performance = 'performance';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Initial Assignment',
            self::Manual => 'Manual Reassignment',
            self::MilestoneFailure => 'Milestone Failure',
            self::Performance => 'Performance Reassignment',
        };
    }
}
