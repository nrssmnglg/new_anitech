<?php

namespace App\Enums;

enum DocumentVerificationStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
        };
    }

    public function isAccepted(): bool
    {
        return $this === self::VERIFIED;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
