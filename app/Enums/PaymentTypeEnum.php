<?php

namespace App\Enums;

use App\Enums\WalletTypeEnum;

enum PaymentTypeEnum: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CHEQUE = 'cheque';
    case CARD = 'card';
    case MOBILE_WALLET = 'mobile_wallet';
    case SSL_GATEWAY = 'ssl_gateway';
    case OTHER = 'other';
    case AMAR_PAY = 'amar_pay';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CHEQUE => 'Cheque',
            self::CARD => 'Card',
            self::MOBILE_WALLET => 'Mobile Wallet',
            self::SSL_GATEWAY => 'SSL Commerz',
            self::OTHER => 'Other',
            self::AMAR_PAY => 'Amar Pay',
        };
    }

    public function options(): ?array
    {
        return match ($this) {
            self::MOBILE_WALLET => WalletTypeEnum::options(),
            default => null,
        };
    }
}
