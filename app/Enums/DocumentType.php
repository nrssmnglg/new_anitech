<?php

namespace App\Enums;

enum DocumentType: string
{
    case BIRTH_CERTIFICATE = 'birth_certificate';
    case CEDULA = 'cedula';
    case TWO_BY_TWO_PICTURE = 'two_by_two_picture';
    case OFFICE_MEMBERSHIP_FORM = 'office_membership_form';
    case APPLICATION_FORM = 'application_form';
    case GOVERNMENT_ID = 'government_id';
    case BARANGAY_CERTIFICATION = 'barangay_certification';
    case PROOF_OF_FARMING = 'proof_of_farming';
    case PROFILE_PHOTO = 'profile_photo';
    case PAYMENT_RECEIPT = 'payment_receipt';
    case SENIOR_CITIZEN_ID = 'senior_citizen_id';
    case PREVIOUS_MEMBERSHIP_ID = 'previous_membership_id';
    case REACTIVATION_LETTER = 'reactivation_letter';

    public function label(): string
    {
        return match ($this) {
            self::BIRTH_CERTIFICATE => 'Birth Certificate',
            self::CEDULA => 'Cedula',
            self::TWO_BY_TWO_PICTURE => '2x2 Picture',
            self::OFFICE_MEMBERSHIP_FORM => 'Office Membership Form',
            self::APPLICATION_FORM => 'Application Form',
            self::GOVERNMENT_ID => 'Government ID',
            self::BARANGAY_CERTIFICATION => 'Barangay Certification',
            self::PROOF_OF_FARMING => 'Proof of Farming',
            self::PROFILE_PHOTO => 'Profile Photo',
            self::PAYMENT_RECEIPT => 'Payment Receipt',
            self::SENIOR_CITIZEN_ID => 'Senior Citizen ID',
            self::PREVIOUS_MEMBERSHIP_ID => 'Previous Membership ID',
            self::REACTIVATION_LETTER => 'Reactivation Letter',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
