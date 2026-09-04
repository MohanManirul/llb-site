<?php

namespace App\Enums;

enum WalletTypeEnum: string
{
    case BKASH = 'bkash';
    case NAGAD = 'nagad';
    case ROCKET = 'rocket';

    public function label(): string
    {
        return match ($this) {
            self::BKASH => 'bKash',
            self::NAGAD => 'Nagad',
            self::ROCKET => 'Rocket',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
