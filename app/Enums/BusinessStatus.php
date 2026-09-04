<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case BusinessSetup = 'business_setup';
    case CampaignRunning = 'campaign_running';
    case CampaignOff = 'campaign_off';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::BusinessSetup => 'Business Setup',
            self::CampaignRunning => 'Campaign Running',
            self::CampaignOff => 'Campaign Off',
            self::OnHold => 'On Hold',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BusinessSetup => '#6366F1',
            self::CampaignRunning => '#10B981',
            self::CampaignOff => '#F59E0B',
            self::OnHold => '#64748B',
            self::Completed => '#3B82F6',
            self::Closed => '#EF4444',
        };
    }

    /**
     * @return list<array<string, string>>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
