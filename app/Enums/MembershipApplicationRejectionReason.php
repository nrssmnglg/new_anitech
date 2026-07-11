<?php

namespace App\Enums;

enum MembershipApplicationRejectionReason: string
{
    case MISSING_DOCUMENTS = 'missing_documents';
    case INVALID_PERSONAL_DETAILS = 'invalid_personal_details';
    case DUPLICATE_FARMER_RECORD = 'duplicate_farmer_record';
    case NOT_ELIGIBLE = 'not_eligible';
    case UNVERIFIED_REQUIREMENTS = 'unverified_requirements';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MISSING_DOCUMENTS => 'Missing Documents',
            self::INVALID_PERSONAL_DETAILS => 'Invalid Personal Details',
            self::DUPLICATE_FARMER_RECORD => 'Duplicate Farmer Record',
            self::NOT_ELIGIBLE => 'Not Eligible',
            self::UNVERIFIED_REQUIREMENTS => 'Unverified Requirements',
            self::OTHER => 'Other',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $reason) => [$reason->value => $reason->label()])
            ->all();
    }
}
