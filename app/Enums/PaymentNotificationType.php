<?php

namespace App\Enums;

enum PaymentNotificationType: string
{
    case PaymentDueReminder = 'payment_due_reminder';
    case PaymentOverdueReminder = 'payment_overdue_reminder';
    case PaymentReceivedConfirmation = 'payment_received_confirmation';
    case InvoiceGenerated = 'invoice_generated';

    public function label(): string
    {
        return match ($this) {
            self::PaymentDueReminder => 'Upcoming payment',
            self::PaymentOverdueReminder => 'Payment overdue',
            self::PaymentReceivedConfirmation => 'Payment received',
            self::InvoiceGenerated => 'Invoice generated',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
