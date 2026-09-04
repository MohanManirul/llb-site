<?php

namespace App\Enums;

enum ProjectType: string
{
    case Regular = 'regular';
    case ChallengeBased = 'challenge_based';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::ChallengeBased => 'Challenge Based',
        };
    }

    public function requiresSalesGoal(): bool
    {
        return $this === self::ChallengeBased;
    }
}
