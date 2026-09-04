<?php

namespace App\Enums;

enum HealthStatus: string
{
    case Upcoming = 'upcoming';
    case OnTrack = 'on_track';
    case AtRisk = 'at_risk';
    case OffTrack = 'off_track';

    public function color(): string
    {
        return match ($this) {
            self::Upcoming => 'gray',
            self::OnTrack => 'green',
            self::AtRisk => 'yellow',
            self::OffTrack => 'red',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming',
            self::OnTrack => 'On Track',
            self::AtRisk => 'At Risk',
            self::OffTrack => 'Off Track',
        };
    }

    /**
     * @param  float  $ratio  achieved / target
     */
    public static function fromRatio(float $ratio, bool $periodStarted = true): self
    {
        if (! $periodStarted) {
            return self::Upcoming;
        }

        return match (true) {
            $ratio >= 0.9 => self::OnTrack,
            $ratio >= 0.6 => self::AtRisk,
            default => self::OffTrack,
        };
    }
}
