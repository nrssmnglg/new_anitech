<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERPAID = 'overpaid';
    case WAIVED = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
            self::PARTIALLY_PAID => 'Recorded',
            self::PAID => 'Paid',
            self::OVERPAID => 'Overpaid',
            self::WAIVED => 'Waived',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::PAID, self::OVERPAID, self::WAIVED], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

