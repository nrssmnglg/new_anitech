<?php

namespace App\Enums;

enum NotificationType: string
{
    case MEMBERSHIP_APPLICATION_SUBMITTED = 'membership_application_submitted';
    case MEMBERSHIP_APPLICATION_UPDATED = 'membership_application_updated';
    case MEMBERSHIP_APPLICATION_APPROVED = 'membership_application_approved';
    case MEMBERSHIP_APPLICATION_REJECTED = 'membership_application_rejected';
    case DOCUMENT_VERIFIED = 'document_verified';
    case PAYMENT_ASSESSED = 'payment_assessed';
    case PAYMENT_RECORDED = 'payment_recorded';
    case RENEWAL_REQUEST_SUBMITTED = 'renewal_request_submitted';
    case RENEWAL_REQUEST_APPROVED = 'renewal_request_approved';
    case RENEWAL_REQUEST_REJECTED = 'renewal_request_rejected';
    case RENEWAL_REMINDER = 'renewal_reminder';
    case REACTIVATION_SUBMITTED = 'reactivation_submitted';
    case FARMER_MARKED_INACTIVE = 'farmer_marked_inactive';
    case ADVISORY_PUBLISHED = 'advisory_published';
    case QUERY_RECEIVED = 'query_received';
    case QUERY_RESPONDED = 'query_responded';
    case MORTUARY_CLAIM_FILED = 'mortuary_claim_filed';

    public function label(): string
    {
        return match ($this) {
            self::MEMBERSHIP_APPLICATION_SUBMITTED => 'New Membership Application',
            self::MEMBERSHIP_APPLICATION_UPDATED => 'New Membership Application',
            self::MEMBERSHIP_APPLICATION_APPROVED => 'Membership Application Approved',
            self::MEMBERSHIP_APPLICATION_REJECTED => 'Membership Application Rejected',
            self::DOCUMENT_VERIFIED => 'Document Verified',
            self::PAYMENT_ASSESSED => 'Payment Assessed',
            self::PAYMENT_RECORDED => 'Payment Recorded',
            self::RENEWAL_REQUEST_SUBMITTED => 'Membership Renewal for ' . now()->year,
            self::RENEWAL_REQUEST_APPROVED => 'Renewal Request Approved',
            self::RENEWAL_REQUEST_REJECTED => 'Renewal Request Rejected',
            self::RENEWAL_REMINDER => 'Renewal Reminder',
            self::REACTIVATION_SUBMITTED => 'Reactivation Submitted',
            self::FARMER_MARKED_INACTIVE => 'Farmer Marked Inactive',
            self::ADVISORY_PUBLISHED => 'Advisory Published',
            self::QUERY_RECEIVED => 'Query Received',
            self::QUERY_RESPONDED => 'Query Responded',
            self::MORTUARY_CLAIM_FILED => 'Mortuary Claim Filed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
