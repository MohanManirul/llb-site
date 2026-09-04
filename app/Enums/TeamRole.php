<?php

namespace App\Enums;

enum TeamRole: string
{
    case Leader = 'leader';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Leader => 'Team Leader',
            self::Member => 'Member',
        };
    }
}
