<?php

namespace App\Services\Farmer;

use App\Models\Query;
use App\Models\User;
use Carbon\Carbon;

class FarmerDashboardService
{
    public function __construct(
        private readonly FarmerAdvisoryService $advisoryService,
        private readonly FarmerInquiryService $inquiryService,
        private readonly FarmerNotificationService $notificationService,
        private readonly FarmerRenewalService $renewalService,
    ) {
    }

    public function build(User $user): array
    {
        $farmer = $user->farmer->load(['profile', 'barangay', 'association', 'memberType']);
        $latestApplication = $farmer->membershipApplications()
            ->with(['documents', 'paymentAssessments.payments'])
            ->latest('submitted_at')
            ->latest('id')
            ->first();
        $latestRenewal = $farmer->renewalRequests()
            ->with(['paymentAssessments.payments'])
            ->latest('year')
            ->latest('id')
            ->first();
        $latestAdvisory = $this->advisoryService->queryForFarmer($farmer)
            ->latest('published_at')
            ->first();
        $latestNotification = $this->notificationService->list($farmer, 1)->first();
        $latestInquiryWithReply = Query::query()
            ->where('farmer_id', $farmer->id)
            ->with(['responses' => fn ($query) => $query->latest('responded_at')->limit(1), 'responses.responder:id,name,role'])
            ->whereHas('responses')
            ->latest('created_at')
            ->latest('id')
            ->first();
        $notificationSummary = $this->notificationService->summary($farmer);
        $inquirySummary = $this->inquiryService->summary($farmer);
        $profileCompleteness = $this->profileCompleteness($farmer);
        $applicationSummary = $this->applicationSummary($latestApplication);
        $renewalSummary = $this->renewalSummary($latestRenewal);
        $latestPayment = $this->latestPaymentSummary($applicationSummary, $renewalSummary);
        $reminders = $this->buildReminders($applicationSummary, $renewalSummary, $profileCompleteness, $notificationSummary, $latestInquiryWithReply);

        return [
            'user' => $user,
            'farmer' => $farmer,
            'stats' => [
                'membership_status' => $farmer->membership_status?->value ?? (string) $farmer->membership_status,
                'farmer_status' => $farmer->status->value,
                'renewal_year' => now()->year,
                'open_inquiries' => $inquirySummary['open'],
                'answered_inquiries' => $inquirySummary['resolved'],
                'unread_notifications' => $notificationSummary['unread'],
            ],
            'action_items' => $reminders['cards'],
            'priority_reminders' => $reminders['priority'],
            'activity_summary' => [
                'latest_application' => $applicationSummary,
                'latest_renewal' => $renewalSummary,
                'last_payment' => $latestPayment,
                'unread_notifications' => $notificationSummary['unread'],
                'recent_inquiry_reply' => $this->recentInquiryReply($latestInquiryWithReply),
            ],
            'profile_completion' => $profileCompleteness,
            'current_renewal' => $renewalSummary,
            'latest_advisory' => $latestAdvisory ? [
                'id' => $latestAdvisory->id,
                'title' => $latestAdvisory->title,
                'slug' => $latestAdvisory->slug,
                'published_at' => optional($latestAdvisory->published_at)->toIso8601String(),
            ] : null,
            'quick_actions' => $this->quickActions($applicationSummary, $renewalSummary),
            'latest_notification' => $latestNotification ? [
                'subject' => $latestNotification->subject,
                'message' => $latestNotification->message,
                'created_at' => optional($latestNotification->created_at)->toIso8601String(),
            ] : null,
        ];
    }

    private function applicationSummary(object|null $application): ?array
    {
        if (! $application) {
            return null;
        }

        $documents = $application->documents ?? collect();
        $totalDocuments = $documents->count();
        $uploadedDocuments = $documents->filter(fn ($document) => filled($document->file_path ?? $document->path ?? null))->count();
        $correctionCount = $documents->filter(fn ($document) => ($document->verification_status?->value ?? (string) $document->verification_status) === 'rejected')->count();
        $assessment = $application->paymentAssessments?->sortByDesc('id')->first();

        return [
            'application_no' => $application->application_no,
            'status' => $application->status?->value ?? (string) $application->status,
            'status_label' => $application->status?->label() ?? ucfirst((string) $application->status),
            'submitted_at' => optional($application->submitted_at)->toIso8601String(),
            'missing_documents' => max(0, $totalDocuments - $uploadedDocuments),
            'correction_required' => $correctionCount > 0 || ($application->status?->value ?? (string) $application->status) === 'rejected',
            'payment_needed' => $assessment && ! in_array($assessment->status?->value ?? (string) $assessment->status, ['paid', 'overpaid', 'waived'], true),
            'payment_status' => $assessment?->status?->label() ?? ucfirst((string) ($assessment?->status?->value ?? $assessment?->status ?? 'pending')),
        ];
    }

    private function renewalSummary(object|null $renewal): ?array
    {
        if (! $renewal) {
            return null;
        }

        $assessment = $renewal->paymentAssessments?->sortByDesc('id')->first();
        $renewalDeadline = Carbon::create($renewal->year, 12, 31, 23, 59, 59);

        return [
            'id' => $renewal->id,
            'year' => $renewal->year,
            'status' => $renewal->status?->value ?? (string) $renewal->status,
            'status_label' => $renewal->status?->label() ?? ucfirst((string) $renewal->status),
            'submitted_at' => optional($renewal->submitted_at)->toIso8601String(),
            'deadline' => $renewalDeadline->toIso8601String(),
            'deadline_soon' => now()->diffInDays($renewalDeadline, false) <= 30,
            'payment_needed' => $assessment && ! in_array($assessment->status?->value ?? (string) $assessment->status, ['paid', 'overpaid', 'waived'], true),
            'payment_status' => $assessment?->status?->label() ?? ucfirst((string) ($assessment?->status?->value ?? $assessment?->status ?? 'pending')),
            'total_due' => $assessment?->total_amount_due !== null ? (float) $assessment->total_amount_due : null,
        ];
    }

    private function latestPaymentSummary(?array $applicationSummary, ?array $renewalSummary): ?array
    {
        if ($renewalSummary && filled($renewalSummary['payment_status'] ?? null)) {
            return [
                'module' => 'renewal',
                'status' => $renewalSummary['payment_status'],
                'total_due' => $renewalSummary['total_due'] ?? null,
            ];
        }

        if ($applicationSummary && filled($applicationSummary['payment_status'] ?? null)) {
            return [
                'module' => 'application',
                'status' => $applicationSummary['payment_status'],
                'total_due' => null,
            ];
        }

        return null;
    }

    private function recentInquiryReply(?Query $query): ?array
    {
        $response = $query?->responses?->first();

        if (! $query || ! $response) {
            return null;
        }

        return [
            'subject' => $query->subject,
            'message' => $response->message,
            'responded_at' => optional($response->responded_at)->toIso8601String(),
            'responder_name' => $response->responder?->name ?? 'AniTech Support',
        ];
    }

    private function profileCompleteness(object $farmer): array
    {
        $profile = $farmer->profile;
        $checks = [
            'first_name' => filled($profile?->first_name),
            'last_name' => filled($profile?->last_name),
            'birth_date' => filled($profile?->birth_date),
            'mobile_number' => filled($profile?->mobile_number),
            'address' => filled($profile?->address),
            'barangay' => $farmer->barangay_id !== null,
            'association' => $farmer->association_id !== null,
        ];

        $completed = collect($checks)->filter()->count();
        $total = count($checks);

        return [
            'completed' => $completed,
            'total' => $total,
            'is_incomplete' => $completed < $total,
            'missing_fields' => collect($checks)
                ->filter(fn ($done) => ! $done)
                ->keys()
                ->map(fn ($field) => str_replace('_', ' ', (string) $field))
                ->values()
                ->all(),
        ];
    }

    private function buildReminders(?array $application, ?array $renewal, array $profileCompleteness, array $notificationSummary, ?Query $latestInquiryWithReply): array
    {
        $cards = [];
        $priority = [];

        $pushCard = function (string $key, string $label, string $value, string $routeName) use (&$cards): void {
            $cards[] = compact('key', 'label', 'value', 'routeName');
        };

        if ($renewal && ($renewal['deadline_soon'] ?? false)) {
            $pushCard('renewal_due', 'Renewal Due', 'Deadline approaching', 'renewals');
            $priority[] = 'Your renewal deadline is approaching. Review it now.';
        }

        if ($application && ($application['missing_documents'] ?? 0) > 0) {
            $pushCard('missing_documents', 'Missing Documents', (string) $application['missing_documents'], 'upload');
        }

        if ($latestInquiryWithReply) {
            $pushCard('inquiry_reply', 'Inquiry Reply Available', 'New response received', 'inquiries');
        }

        if (($renewal && ($renewal['payment_needed'] ?? false)) || ($application && ($application['payment_needed'] ?? false))) {
            $pushCard('payment_needed', 'Payment Needed', 'Pending verification or payment', 'payments');
            $priority[] = 'A payment is still pending. Open the payment or renewal screen now.';
        }

        if ($application && ($application['correction_required'] ?? false)) {
            $pushCard('correction_required', 'Correction Required', 'Update and resubmit', 'track-status');
        }

        if ($profileCompleteness['is_incomplete']) {
            $pushCard('profile_incomplete', 'Profile Incomplete', count($profileCompleteness['missing_fields']) . ' fields missing', 'membership');
        }

        if (($notificationSummary['unread'] ?? 0) > 0) {
            $priority[] = 'You have unread notifications that may need action.';
        }

        return [
            'cards' => $cards,
            'priority' => array_values(array_unique($priority)),
        ];
    }

    private function quickActions(?array $application, ?array $renewal): array
    {
        return [
            [
                'label' => 'Continue Application',
                'route_name' => 'apply',
                'visible' => $application !== null,
            ],
            [
                'label' => 'Upload Documents',
                'route_name' => 'upload',
                'visible' => $application !== null,
            ],
            [
                'label' => 'Track Status',
                'route_name' => 'track-status',
                'visible' => $application !== null,
            ],
            [
                'label' => 'Send Inquiry',
                'route_name' => 'inquiries',
                'visible' => true,
            ],
            [
                'label' => 'Pay Renewal',
                'route_name' => 'renewals',
                'visible' => $renewal !== null,
            ],
        ];
    }
}
