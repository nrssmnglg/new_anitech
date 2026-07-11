<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case PENDING_APPLICATION = 'pending_application';
    case PENDING_DOCUMENTS = 'pending_documents';
    case PENDING_VERIFICATION = 'pending_verification';
    case PENDING_PAYMENT = 'pending_payment';
    case ACTIVE = 'active';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_APPLICATION => 'Pending Application',
            self::PENDING_DOCUMENTS => 'Pending Documents',
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::PENDING_PAYMENT => 'Pending Payment',
            self::ACTIVE => 'Active',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}