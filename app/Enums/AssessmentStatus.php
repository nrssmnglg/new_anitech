<?php

namespace App\Enums;

enum AssessmentStatus: string
{
    case PENDING = 'pending';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERPAID = 'overpaid';
    case WAIVED = 'waived';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PARTIALLY_PAID => 'Recorded',
            self::PAID => 'Paid',
            self::OVERPAID => 'Overpaid',
            self::WAIVED => 'Waived',
            self::CANCELLED => 'Cancelled',
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

