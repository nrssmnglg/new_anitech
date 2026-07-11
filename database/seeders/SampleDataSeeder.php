<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MembershipApplicationRejectionReason;
use App\Enums\MembershipStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\ReactivationStatus;
use App\Enums\RenewalStatus;
use App\Models\Advisory;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerDocument;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Models\MortuaryClaim;
use App\Models\Payment;
use App\Models\PaymentAssessment;
use App\Models\Query;
use App\Models\QueryResponse;
use App\Models\RenewalRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SampleDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SAMPLE_BATCH = 'sample-data-seeder-v2';
    private const SAMPLE_COUNT = 5;
    private const APPLICATION_SAMPLE_COUNT = 3;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            BarangaySeeder::class,
            AssociationSeeder::class,
            MemberTypeSeeder::class,
            FeeScheduleSeeder::class,
        ]);

        DB::transaction(function (): void {
            $now = now()->startOfSecond();
            $currentYear = (int) $now->year;

            $this->purgeSampleData();

            $officeUsers = $this->seedOfficeUsers($now);
            $admin = $officeUsers->firstWhere('email', 'admin@anitech.test');

            if (! $admin instanceof User) {
                throw new RuntimeException('The default admin account could not be created.');
            }

            $staffUsers = $officeUsers
                ->filter(fn (User $user): bool => $user->email !== 'admin@anitech.test')
                ->values();

            $barangays = Barangay::query()->orderBy('id')->take(self::SAMPLE_COUNT)->get()->values();

            if ($barangays->count() < self::SAMPLE_COUNT) {
                throw new RuntimeException('SampleDataSeeder requires at least 5 barangays.');
            }

            $associations = Association::query()
                ->whereIn('barangay_id', $barangays->modelKeys())
                ->orderBy('barangay_id')
                ->get()
                ->keyBy('barangay_id');

            if ($associations->count() < self::SAMPLE_COUNT) {
                throw new RuntimeException('SampleDataSeeder requires associations for the first 5 barangays.');
            }

            $memberTypes = MemberType::query()
                ->whereIn('code', ['NM', 'OM', 'NSC', 'OSC'])
                ->get()
                ->keyBy('code');

            if ($memberTypes->count() < 4) {
                throw new RuntimeException('SampleDataSeeder requires all member types to exist.');
            }

            $feeSchedule = $this->resolveFeeSchedule($currentYear, $now);

            $farmers = $this->seedFarmers($barangays, $associations, $memberTypes, $currentYear, $now);
            $farmerUsers = $this->seedFarmerUsers($farmers, $admin, $now);
            $applications = $this->seedApplications($farmers, $staffUsers, $now);
            $renewals = $this->seedRenewals($farmers, $staffUsers, $currentYear, $now);

            $this->linkFarmersToApplications($farmers, $applications);
            $this->seedFarmerDocuments($farmers, $applications, $renewals, $staffUsers, $now);

            $assessments = $this->seedPaymentAssessments($farmers, $applications, $renewals, $feeSchedule, $now);
            $payments = $this->seedPayments($assessments, $staffUsers, $now);
            $ledgers = $this->seedMembershipLedgers($farmers, $applications, $renewals, $assessments, $currentYear, $now);

            $this->seedReactivationRequests($farmers, $staffUsers, $now);
            $this->seedMortuaryClaims($farmers, $ledgers, $staffUsers, $now);

            $advisories = $this->seedAdvisories($admin, $barangays, $associations, $memberTypes, $now);
            $this->seedAdvisoryAttachments($advisories, $now);

            $queries = $this->seedQueries($farmers, $now);
            $responses = $this->seedQueryResponses($queries, $staffUsers, $now);
            $this->seedQueryImages($queries, $now);
            $this->seedQueryResponseAttachments($responses, $now);

            $notifications = $this->seedNotifications($admin, $now);
            $this->seedNotificationRecipients($notifications, $farmers, $farmerUsers, $now);
            $this->seedPaymongoWebhookEvents($assessments, $applications, $renewals, $payments, $now);
            $this->seedPasswordResetOtps($farmerUsers, $staffUsers, $now);
        });

        $this->call(OldMemberWithout2026RenewalWithAccountSeeder::class);
    }

    private function purgeSampleData(): void
    {
        $sampleFarmerIds = Farmer::query()
            ->where('farmer_code', 'like', 'SMP-FRM-%')
            ->pluck('id');

        $sampleUserIds = User::query()
            ->where('email', 'like', '%@sample.anitech.test')
            ->pluck('id');

        $applicationIds = MembershipApplication::query()
            ->whereIn('farmer_id', $sampleFarmerIds)
            ->pluck('id');

        $renewalIds = RenewalRequest::query()
            ->whereIn('farmer_id', $sampleFarmerIds)
            ->pluck('id');

        $assessmentIds = PaymentAssessment::query()
            ->whereIn('farmer_id', $sampleFarmerIds)
            ->orWhereIn('membership_application_id', $applicationIds)
            ->orWhereIn('renewal_request_id', $renewalIds)
            ->pluck('id');

        $ledgerIds = MembershipLedger::query()
            ->whereIn('farmer_id', $sampleFarmerIds)
            ->pluck('id');

        $queryIds = Query::query()
            ->whereIn('farmer_id', $sampleFarmerIds)
            ->pluck('id');

        $queryResponseIds = DB::table('query_responses')
            ->whereIn('query_id', $queryIds)
            ->pluck('id');

        $advisoryIds = Advisory::query()
            ->where('slug', 'like', 'sample-advisory-%')
            ->pluck('id');

        $notificationIds = DB::table('notifications')
            ->where('subject', 'like', 'Sample %')
            ->pluck('id');

        DB::table('query_response_attachments')->whereIn('query_response_id', $queryResponseIds)->delete();
        DB::table('query_responses')->whereIn('id', $queryResponseIds)->delete();
        DB::table('query_images')->whereIn('query_id', $queryIds)->delete();
        DB::table('queries')->whereIn('id', $queryIds)->delete();

        DB::table('notification_recipients')->whereIn('notification_id', $notificationIds)->delete();
        DB::table('notifications')->whereIn('id', $notificationIds)->delete();

        DB::table('advisory_member_type')->whereIn('advisory_id', $advisoryIds)->delete();
        DB::table('advisory_attachments')->whereIn('advisory_id', $advisoryIds)->delete();
        DB::table('advisories')->whereIn('id', $advisoryIds)->delete();

        DB::table('paymongo_webhook_events')->whereIn('payment_assessment_id', $assessmentIds)->delete();
        DB::table('payments')->whereIn('payment_assessment_id', $assessmentIds)->delete();
        DB::table('mortuary_claims')->whereIn('membership_ledger_id', $ledgerIds)->delete();
        DB::table('farmer_documents')->whereIn('farmer_id', $sampleFarmerIds)->delete();
        DB::table('membership_ledgers')->whereIn('id', $ledgerIds)->delete();
        DB::table('payment_assessments')->whereIn('id', $assessmentIds)->delete();
        DB::table('reactivation_requests')->whereIn('farmer_id', $sampleFarmerIds)->delete();
        DB::table('renewal_requests')->whereIn('id', $renewalIds)->delete();
        DB::table('membership_applications')->whereIn('id', $applicationIds)->delete();

        DB::table('farmer_password_reset_otps')->whereIn('user_id', $sampleUserIds)->delete();
        DB::table('office_password_reset_otps')->whereIn('user_id', $sampleUserIds)->delete();
        User::query()->where('email', 'like', '%@sample.anitech.test')->delete();
        Farmer::query()->where('farmer_code', 'like', 'SMP-FRM-%')->delete();
    }

    private function seedOfficeUsers(Carbon $now): Collection
    {
        $definitions = [
            [
                'email' => 'admin@anitech.test',
                'name' => 'AniTech Admin',
                'role' => User::ROLE_ADMIN,
                'ip' => '192.168.10.10',
            ],
            [
                'email' => 'staff@anitech.test',
                'name' => 'AniTech Staff',
                'role' => User::ROLE_STAFF,
                'ip' => '192.168.10.11',
            ],
            [
                'email' => 'office03@sample.anitech.test',
                'name' => 'Sample Staff 03',
                'role' => User::ROLE_STAFF,
                'ip' => '192.168.10.12',
            ],
            [
                'email' => 'office04@sample.anitech.test',
                'name' => 'Sample Staff 04',
                'role' => User::ROLE_STAFF,
                'ip' => '192.168.10.13',
            ],
            [
                'email' => 'office05@sample.anitech.test',
                'name' => 'Sample Staff 05',
                'role' => User::ROLE_STAFF,
                'ip' => '192.168.10.14',
            ],
        ];

        return collect($definitions)->map(function (array $definition, int $index) use ($now): User {
            $user = User::query()->updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => 'password123',
                    'status' => 'active',
                    'farmer_id' => null,
                    'created_by' => null,
                    'must_change_password' => false,
                    'last_login_at' => $now->copy()->subDays($index + 1),
                    'last_login_ip' => $definition['ip'],
                ],
            );

            if (! $user->hasRole($definition['role'])) {
                $user->syncRoles([$definition['role']]);
            }

            return $user;
        });
    }

    private function resolveFeeSchedule(int $year, Carbon $now): FeeSchedule
    {
        FeeSchedule::query()->updateOrCreate(
            ['year' => $year - 1],
            [
                'membership_fee' => 100.00,
                'annual_due' => 100.00,
                'mortuary_fee' => 150.00,
                'renewal_deadline' => Carbon::create($year - 1, 2, 14)->toDateString(),
                'is_active' => false,
                'effective_from' => Carbon::create($year - 1, 1, 1)->toDateString(),
                'effective_to' => Carbon::create($year - 1, 12, 31)->toDateString(),
            ],
        );

        return FeeSchedule::query()->updateOrCreate(
            ['year' => $year],
            [
                'membership_fee' => 100.00,
                'annual_due' => 100.00,
                'mortuary_fee' => 150.00,
                'renewal_deadline' => Carbon::create($year, 2, 14)->toDateString(),
                'is_active' => true,
                'effective_from' => $now->copy()->startOfYear()->toDateString(),
                'effective_to' => null,
            ],
        );
    }

    private function seedFarmers(
        Collection $barangays,
        Collection $associations,
        Collection $memberTypes,
        int $currentYear,
        Carbon $now,
    ): Collection {
        $definitions = [
            [
                'first_name' => 'Marvin',
                'middle_name' => 'Soriano',
                'last_name' => 'Aguilar',
                'sex' => 'Male',
                'civil_status' => 'Married',
                'member_type_code' => 'NM',
                'status' => FarmerStatus::ACTIVE,
                'membership_status' => MembershipStatus::ACTIVE,
                'record_origin' => 'application',
                'last_renewal_year' => $currentYear,
                'birth_date' => '1987-06-14',
                'mobile_number' => '09171234561',
                'email' => 'marvin.aguilar@sample.anitech.test',
                'address' => 'Purok 2, San Vicente, San Carlos City, Pangasinan',
                'remarks' => 'Corn farmer with an approved first-time membership application.',
            ],
            [
                'first_name' => 'Jennifer',
                'middle_name' => 'Mendoza',
                'last_name' => 'Bautista',
                'sex' => 'Female',
                'civil_status' => 'Married',
                'member_type_code' => 'NM',
                'status' => FarmerStatus::PENDING,
                'membership_status' => MembershipStatus::PENDING_DOCUMENTS,
                'record_origin' => 'application',
                'last_renewal_year' => null,
                'birth_date' => '1992-11-03',
                'mobile_number' => '09184567234',
                'email' => 'jennifer.bautista@sample.anitech.test',
                'address' => 'Sitio Centro, Rizal, San Carlos City, Pangasinan',
                'remarks' => 'Vegetable grower who submitted online and still needs to complete supporting documents.',
            ],
            [
                'first_name' => 'Rogelio',
                'middle_name' => 'Villanueva',
                'last_name' => 'Castro',
                'sex' => 'Male',
                'civil_status' => 'Married',
                'member_type_code' => 'NSC',
                'status' => FarmerStatus::ACTIVE,
                'membership_status' => MembershipStatus::PENDING_VERIFICATION,
                'record_origin' => 'application',
                'last_renewal_year' => $currentYear - 1,
                'birth_date' => '1958-02-22',
                'mobile_number' => '09192345678',
                'email' => 'rogelio.castro@sample.anitech.test',
                'address' => 'Purok 4, Bocboc, San Carlos City, Pangasinan',
                'remarks' => 'Senior rice farmer with a walk-in application currently under verification.',
            ],
            [
                'first_name' => 'Norma',
                'middle_name' => 'De Leon',
                'last_name' => 'Ramos',
                'sex' => 'Female',
                'civil_status' => 'Widowed',
                'member_type_code' => 'OM',
                'status' => FarmerStatus::ACTIVE,
                'membership_status' => MembershipStatus::PENDING_PAYMENT,
                'record_origin' => 'legacy',
                'last_renewal_year' => $currentYear - 1,
                'birth_date' => '1974-09-17',
                'mobile_number' => '09175678901',
                'email' => 'norma.ramos@sample.anitech.test',
                'address' => 'Purok 1, Bega, San Carlos City, Pangasinan',
                'remarks' => 'Long-time member who filed a renewal and is waiting for payment confirmation.',
            ],
            [
                'first_name' => 'Teodoro',
                'middle_name' => 'Espino',
                'last_name' => 'Garcia',
                'sex' => 'Male',
                'civil_status' => 'Married',
                'member_type_code' => 'OM',
                'status' => FarmerStatus::ACTIVE,
                'membership_status' => MembershipStatus::ACTIVE,
                'record_origin' => 'legacy',
                'last_renewal_year' => $currentYear - 1,
                'birth_date' => '1969-01-09',
                'mobile_number' => '09166789012',
                'email' => 'teodoro.garcia@sample.anitech.test',
                'address' => 'Purok 6, Coliling, San Carlos City, Pangasinan',
                'remarks' => 'Old member with a completed renewal and updated ledger for the current year.',
            ],
        ];

        return collect($definitions)->map(function (array $definition, int $index) use ($barangays, $associations, $memberTypes, $now): Farmer {
            $barangay = $barangays->get($index);
            $association = $associations->get($barangay->id);
            $memberType = $memberTypes->get($definition['member_type_code']);
            $status = $definition['status'];

            return Farmer::query()->create([
                'farmer_code' => sprintf('SMP-FRM-%04d', $index + 1),
                'first_name' => $definition['first_name'],
                'middle_name' => $definition['middle_name'],
                'last_name' => $definition['last_name'],
                'suffix' => null,
                'sex' => $definition['sex'],
                'birth_date' => $definition['birth_date'],
                'civil_status' => $definition['civil_status'],
                'mobile_number' => $definition['mobile_number'],
                'email' => $definition['email'],
                'address' => $definition['address'],
                'barangay_id' => $barangay->id,
                'association_id' => $association?->id,
                'member_type_id' => $memberType?->id,
                'is_registry_record' => true,
                'record_origin' => $definition['record_origin'],
                'source_application_id' => null,
                'status' => $status->value,
                'membership_status' => $definition['membership_status']->value,
                'registered_at' => $now->copy()->subMonths(14 - $index),
                'activated_at' => $status === FarmerStatus::ACTIVE ? $now->copy()->subMonths(10 - min($index, 9)) : null,
                'last_renewal_year' => $definition['last_renewal_year'],
                'inactive_at' => in_array($status, [FarmerStatus::INACTIVE, FarmerStatus::DECEASED], true)
                    ? $now->copy()->subMonths($index + 1)
                    : null,
                'inactive_reason' => match ($status) {
                    FarmerStatus::INACTIVE => 'Sample inactive record for reactivation testing.',
                    FarmerStatus::DECEASED => 'Sample deceased record for mortuary assistance.',
                    default => null,
                },
                'remarks' => $definition['remarks'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function seedFarmerUsers(Collection $farmers, User $admin, Carbon $now): Collection
    {
        return $farmers->values()->map(function (Farmer $farmer, int $index) use ($admin, $now): User {
            $user = User::query()->create([
                'name' => $farmer->full_name,
                'email' => $farmer->email,
                'password' => 'password123',
                'status' => $farmer->status === FarmerStatus::ACTIVE ? 'active' : 'inactive',
                'farmer_id' => $farmer->id,
                'created_by' => $admin->id,
                'must_change_password' => false,
                'last_login_at' => $farmer->status === FarmerStatus::ACTIVE ? $now->copy()->subDays($index + 1) : null,
                'last_login_ip' => $farmer->status === FarmerStatus::ACTIVE ? '10.0.0.' . ($index + 21) : null,
            ]);

            $user->syncRoles([User::ROLE_FARMER]);

            return $user;
        });
    }

    private function seedApplications(Collection $farmers, Collection $staffUsers, Carbon $now): Collection
    {
        $statuses = [
            ApplicationStatus::APPROVED,
            ApplicationStatus::SUBMITTED,
            ApplicationStatus::UNDER_REVIEW,
            ApplicationStatus::REJECTED,
            ApplicationStatus::DRAFT,
        ];

        return $farmers->values()->map(function (Farmer $farmer, int $index) use ($staffUsers, $statuses, $now): MembershipApplication {
            $status = $statuses[$index];
            $reviewer = in_array($status, [ApplicationStatus::UNDER_REVIEW, ApplicationStatus::APPROVED, ApplicationStatus::REJECTED], true)
                ? $staffUsers->get($index % $staffUsers->count())
                : null;

            return MembershipApplication::query()->create([
                'farmer_id' => $farmer->id,
                'application_no' => sprintf('SMP-APP-%s-%04d', $now->format('Y'), $index + 1),
                'source' => $index % 2 === 0 ? 'mobile' : 'walk_in',
                'status' => $status->value,
                'submitted_at' => $status === ApplicationStatus::DRAFT ? null : $now->copy()->subDays(30 - $index),
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => $reviewer ? $now->copy()->subDays(20 - min($index, 19)) : null,
                'approved_at' => $status === ApplicationStatus::APPROVED ? $now->copy()->subDays(18 - min($index, 17)) : null,
                'remarks' => $status === ApplicationStatus::REJECTED
                    ? 'Rejected sample application for review testing.'
                    : 'Sample membership application record.',
                'rejection_reason' => $status === ApplicationStatus::REJECTED
                    ? MembershipApplicationRejectionReason::MISSING_DOCUMENTS->value
                    : null,
                'rejection_details' => $status === ApplicationStatus::REJECTED
                    ? 'Government ID and barangay certification were not accepted.'
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function seedRenewals(Collection $farmers, Collection $staffUsers, int $currentYear, Carbon $now): Collection
    {
        $statuses = [
            RenewalStatus::DRAFT,
            RenewalStatus::SUBMITTED,
            RenewalStatus::UNDER_REVIEW,
            RenewalStatus::COMPLETED,
            RenewalStatus::REJECTED,
        ];

        return $farmers->values()->map(function (Farmer $farmer, int $index) use ($staffUsers, $statuses, $currentYear, $now): RenewalRequest {
            $status = $statuses[$index];
            $reviewer = in_array($status, [RenewalStatus::UNDER_REVIEW, RenewalStatus::APPROVED, RenewalStatus::REJECTED, RenewalStatus::COMPLETED], true)
                ? $staffUsers->get($index % $staffUsers->count())
                : null;

            return RenewalRequest::query()->create([
                'farmer_id' => $farmer->id,
                'year' => $currentYear,
                'source' => $index % 2 === 0 ? 'mobile' : 'walk_in',
                'status' => $status->value,
                'submitted_at' => $status === RenewalStatus::DRAFT ? null : $now->copy()->subDays(15 - min($index, 14)),
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => $reviewer ? $now->copy()->subDays(10 - min($index, 9)) : null,
                'approved_at' => in_array($status, [RenewalStatus::APPROVED, RenewalStatus::COMPLETED], true)
                    ? $now->copy()->subDays(7 - min($index, 6))
                    : null,
                'is_late' => $index >= 6,
                'remarks' => $status === RenewalStatus::REJECTED
                    ? 'Renewal requirements need correction.'
                    : 'Sample renewal request.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function linkFarmersToApplications(Collection $farmers, Collection $applications): void
    {
        $farmers->values()->each(function (Farmer $farmer, int $index) use ($applications): void {
            $application = $applications->get($index);

            $farmer->forceFill([
                'source_application_id' => $application?->id,
                'record_origin' => 'application',
            ])->save();
        });
    }

    private function seedFarmerDocuments(
        Collection $farmers,
        Collection $applications,
        Collection $renewals,
        Collection $staffUsers,
        Carbon $now,
    ): void {
        $applicationDocuments = [
            DocumentType::APPLICATION_FORM,
            DocumentType::GOVERNMENT_ID,
            DocumentType::BARANGAY_CERTIFICATION,
            DocumentType::PROOF_OF_FARMING,
        ];

        foreach (range(0, self::APPLICATION_SAMPLE_COUNT - 1) as $index) {
            $application = $applications[$index];
            $farmer = $farmers[$index];

            foreach ($applicationDocuments as $documentIndex => $documentType) {
                $verificationStatus = match ($application->status) {
                    ApplicationStatus::APPROVED => DocumentVerificationStatus::VERIFIED,
                    ApplicationStatus::UNDER_REVIEW => $documentIndex < 2 ? DocumentVerificationStatus::VERIFIED : DocumentVerificationStatus::PENDING,
                    ApplicationStatus::REJECTED => $documentIndex === 1 ? DocumentVerificationStatus::REJECTED : DocumentVerificationStatus::VERIFIED,
                    ApplicationStatus::DRAFT => DocumentVerificationStatus::PENDING,
                    default => DocumentVerificationStatus::PENDING,
                };

                $isReceived = $application->status !== ApplicationStatus::DRAFT && ! ($application->status === ApplicationStatus::SUBMITTED && $documentIndex > 1);
                $staff = $staffUsers[($index + $documentIndex) % $staffUsers->count()];

                FarmerDocument::query()->create([
                    'farmer_id' => $farmer->id,
                    'membership_application_id' => $application->id,
                    'renewal_request_id' => null,
                    'document_type' => $documentType->value,
                    'disk' => 'public',
                    'path' => sprintf('sample-documents/applications/%s/%s.pdf', $application->application_no, $documentType->value),
                    'original_name' => $documentType->value . '.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 125000 + ($documentIndex * 500),
                    'is_required' => true,
                    'is_received' => $isReceived,
                    'received_by' => $isReceived ? $staff->id : null,
                    'received_at' => $isReceived ? $now->copy()->subDays(12 - min($index + $documentIndex, 11)) : null,
                    'verification_status' => $verificationStatus->value,
                    'verified_by' => in_array($verificationStatus, [DocumentVerificationStatus::VERIFIED, DocumentVerificationStatus::REJECTED], true)
                        ? $staff->id
                        : null,
                    'verified_at' => in_array($verificationStatus, [DocumentVerificationStatus::VERIFIED, DocumentVerificationStatus::REJECTED], true)
                        ? $now->copy()->subDays(8 - min($index + $documentIndex, 7))
                        : null,
                    'remarks' => $verificationStatus === DocumentVerificationStatus::REJECTED
                        ? 'Sample rejected document for QA coverage.'
                        : 'Sample application requirement.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $renewalDocuments = [
            DocumentType::PREVIOUS_MEMBERSHIP_ID,
            DocumentType::PAYMENT_RECEIPT,
            DocumentType::GOVERNMENT_ID,
        ];

        foreach (range(self::APPLICATION_SAMPLE_COUNT, self::SAMPLE_COUNT - 1) as $index) {
            $renewal = $renewals[$index];
            $farmer = $farmers[$index];

            foreach ($renewalDocuments as $documentIndex => $documentType) {
                $verificationStatus = match ($renewal->status) {
                    RenewalStatus::COMPLETED, RenewalStatus::APPROVED => DocumentVerificationStatus::VERIFIED,
                    RenewalStatus::UNDER_REVIEW => $documentIndex === 0 ? DocumentVerificationStatus::VERIFIED : DocumentVerificationStatus::PENDING,
                    RenewalStatus::REJECTED => $documentIndex === 1 ? DocumentVerificationStatus::REJECTED : DocumentVerificationStatus::VERIFIED,
                    RenewalStatus::CANCELLED => DocumentVerificationStatus::EXPIRED,
                    default => DocumentVerificationStatus::PENDING,
                };

                $isReceived = ! in_array($renewal->status, [RenewalStatus::DRAFT, RenewalStatus::CANCELLED], true);
                $staff = $staffUsers[($index + $documentIndex) % $staffUsers->count()];

                FarmerDocument::query()->create([
                    'farmer_id' => $farmer->id,
                    'membership_application_id' => null,
                    'renewal_request_id' => $renewal->id,
                    'document_type' => $documentType->value,
                    'disk' => 'public',
                    'path' => sprintf('sample-documents/renewals/%s/%s.pdf', $renewal->id, $documentType->value),
                    'original_name' => $documentType->value . '.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 105000 + ($documentIndex * 750),
                    'is_required' => true,
                    'is_received' => $isReceived,
                    'received_by' => $isReceived ? $staff->id : null,
                    'received_at' => $isReceived ? $now->copy()->subDays(6 - min($documentIndex, 5)) : null,
                    'verification_status' => $verificationStatus->value,
                    'verified_by' => in_array($verificationStatus, [DocumentVerificationStatus::VERIFIED, DocumentVerificationStatus::REJECTED], true)
                        ? $staff->id
                        : null,
                    'verified_at' => in_array($verificationStatus, [DocumentVerificationStatus::VERIFIED, DocumentVerificationStatus::REJECTED], true)
                        ? $now->copy()->subDays(4 - min($documentIndex, 3))
                        : null,
                    'remarks' => $verificationStatus === DocumentVerificationStatus::EXPIRED
                        ? 'Sample expired renewal attachment.'
                        : 'Sample renewal requirement.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function seedPaymentAssessments(
        Collection $farmers,
        Collection $applications,
        Collection $renewals,
        FeeSchedule $feeSchedule,
        Carbon $now,
    ): Collection {
        $statuses = [
            AssessmentStatus::PAID,
            AssessmentStatus::PENDING,
            AssessmentStatus::CANCELLED,
            AssessmentStatus::PENDING,
            AssessmentStatus::PAID,
        ];

        return collect(range(0, self::SAMPLE_COUNT - 1))->map(function (int $index) use ($farmers, $applications, $renewals, $feeSchedule, $statuses, $now): PaymentAssessment {
            $farmer = $farmers[$index];
            $memberTypeCode = (string) $farmer->memberType?->code;
            $membershipFee = in_array($memberTypeCode, ['NM', 'NSC'], true) ? (float) $feeSchedule->membership_fee : 0.0;
            $annualDue = (float) $feeSchedule->annual_due;
            $mortuaryFee = in_array($memberTypeCode, ['NSC', 'OSC'], true) ? 0.0 : (float) $feeSchedule->mortuary_fee;

            return PaymentAssessment::query()->create([
                'farmer_id' => $farmer->id,
                'membership_application_id' => $index < self::APPLICATION_SAMPLE_COUNT ? $applications[$index]->id : null,
                'renewal_request_id' => $index >= self::APPLICATION_SAMPLE_COUNT ? $renewals[$index]->id : null,
                'fee_schedule_id' => $feeSchedule->id,
                'member_type_snapshot' => $memberTypeCode,
                'membership_fee' => $membershipFee,
                'annual_due' => $annualDue,
                'mortuary_fee' => $mortuaryFee,
                'total_amount_due' => $membershipFee + $annualDue + $mortuaryFee,
                'due_date' => $now->copy()->addDays(10 + $index)->toDateString(),
                'status' => $statuses[$index]->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function seedPayments(Collection $assessments, Collection $staffUsers, Carbon $now): Collection
    {
        $statuses = [
            PaymentStatus::PAID,
            PaymentStatus::PENDING,
            PaymentStatus::CANCELLED,
            PaymentStatus::PENDING,
            PaymentStatus::PAID,
        ];

        return $assessments->values()->map(function (PaymentAssessment $assessment, int $index) use ($staffUsers, $statuses, $now): Payment {
            $status = $statuses[$index];
            $amountPaid = match ($status) {
                PaymentStatus::PAID => (float) $assessment->total_amount_due,
                default => 0.0,
            };

            return Payment::query()->create([
                'payment_assessment_id' => $assessment->id,
                'payment_method' => $index % 2 === 0 ? 'cash' : 'gcash',
                'reference_no' => sprintf('SMP-PAY-%s-%04d', $now->format('Y'), $index + 1),
                'amount_paid' => $amountPaid,
                'paid_at' => $now->copy()->subDays(6 - min($index, 5)),
                'verified_by' => $status === PaymentStatus::PAID
                    ? $staffUsers[$index % $staffUsers->count()]->id
                    : null,
                'verified_at' => $status === PaymentStatus::PAID
                    ? $now->copy()->subDays(5 - min($index, 4))
                    : null,
                'status' => $status->value,
                'receipt_no' => 'SMP-RCPT-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'receipt_disk' => 'public',
                'receipt_path' => 'sample-receipts/receipt-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
                'receipt_original_name' => 'receipt-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function seedMembershipLedgers(
        Collection $farmers,
        Collection $applications,
        Collection $renewals,
        Collection $assessments,
        int $currentYear,
        Carbon $now,
    ): Collection {
        return collect(range(0, self::SAMPLE_COUNT - 1))->map(function (int $index) use ($farmers, $applications, $renewals, $assessments, $currentYear, $now): MembershipLedger {
            $assessment = $assessments[$index];
            $paymentStatus = match ($assessment->status) {
                AssessmentStatus::PAID => PaymentStatus::PAID,
                AssessmentStatus::CANCELLED => PaymentStatus::CANCELLED,
                default => PaymentStatus::PENDING,
            };

            $amountPaid = match ($paymentStatus) {
                PaymentStatus::PAID => (float) $assessment->total_amount_due,
                default => 0.0,
            };

            return MembershipLedger::query()->create([
                'farmer_id' => $farmers[$index]->id,
                'year' => $currentYear,
                'membership_application_id' => $index < self::APPLICATION_SAMPLE_COUNT ? $applications[$index]->id : null,
                'renewal_request_id' => $index >= self::APPLICATION_SAMPLE_COUNT ? $renewals[$index]->id : null,
                'member_type_snapshot' => $assessment->member_type_snapshot,
                'membership_fee' => $assessment->membership_fee,
                'annual_due' => $assessment->annual_due,
                'mortuary_fee' => $assessment->mortuary_fee,
                'total_amount_due' => $assessment->total_amount_due,
                'amount_paid' => $amountPaid,
                'paid_at' => $paymentStatus === PaymentStatus::PAID
                    ? $now->copy()->subDays(4 - min($index, 3))
                    : null,
                'payment_status' => $paymentStatus->value,
                'mortuary_eligible' => ! in_array($assessment->member_type_snapshot, ['NSC', 'OSC'], true),
                'status' => $paymentStatus === PaymentStatus::PAID
                    ? 'active'
                    : 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function seedReactivationRequests(Collection $farmers, Collection $staffUsers, Carbon $now): void
    {
        $statuses = [
            ReactivationStatus::SUBMITTED,
            ReactivationStatus::UNDER_REVIEW,
            ReactivationStatus::APPROVED,
            ReactivationStatus::COMPLETED,
            ReactivationStatus::REJECTED,
        ];

        foreach (range(0, self::SAMPLE_COUNT - 1) as $index) {
            $status = $statuses[$index];

            DB::table('reactivation_requests')->insert([
                'farmer_id' => $farmers[$index]->id,
                'status' => $status->value,
                'reason' => 'Sample reactivation reason for farmer ' . ($index + 1) . '.',
                'submitted_at' => $now->copy()->subDays(12 - min($index, 11)),
                'reviewed_by' => in_array($status, [ReactivationStatus::UNDER_REVIEW, ReactivationStatus::APPROVED, ReactivationStatus::COMPLETED, ReactivationStatus::REJECTED], true)
                    ? $staffUsers[$index % $staffUsers->count()]->id
                    : null,
                'reviewed_at' => in_array($status, [ReactivationStatus::UNDER_REVIEW, ReactivationStatus::APPROVED, ReactivationStatus::COMPLETED, ReactivationStatus::REJECTED], true)
                    ? $now->copy()->subDays(8 - min($index, 7))
                    : null,
                'remarks' => 'Sample reactivation workflow state.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedMortuaryClaims(Collection $farmers, Collection $ledgers, Collection $staffUsers, Carbon $now): void
    {
        $statuses = ['pending', 'approved', 'released', 'rejected', 'released'];

        foreach (range(0, self::SAMPLE_COUNT - 1) as $index) {
            $status = $statuses[$index];
            $staff = $staffUsers[$index % $staffUsers->count()];

            MortuaryClaim::query()->create([
                'farmer_id' => $farmers[$index]->id,
                'membership_ledger_id' => $ledgers[$index]->id,
                'claim_reference' => sprintf('SMP-MRT-%s-%04d', $now->format('Y'), $index + 1),
                'claim_amount' => 5000 + ($index * 500),
                'claim_date' => $now->copy()->subDays(20 - min($index, 19))->toDateString(),
                'claimer_name' => 'Claimant ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'claimer_relationship' => $index % 2 === 0 ? 'Spouse' : 'Child',
                'claimer_contact_number' => '09' . str_pad((string) (180000000 + $index + 1), 9, '0', STR_PAD_LEFT),
                'claimer_address' => 'Sample claimant address ' . ($index + 1),
                'claimer_valid_id_received' => true,
                'proof_of_relationship_received' => $index % 4 !== 0,
                'death_certificate_received' => true,
                'death_certificate_disk' => 'public',
                'death_certificate_path' => 'sample-mortuary/death-certificate-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.pdf',
                'death_certificate_original_name' => 'death-certificate-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.pdf',
                'death_certificate_mime_type' => 'application/pdf',
                'status' => $status,
                'filed_by' => $staff->id,
                'approved_by' => in_array($status, ['approved', 'released'], true) ? $staff->id : null,
                'released_by' => $status === 'released' ? $staff->id : null,
                'remarks' => 'Sample mortuary claim record.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedAdvisories(
        User $admin,
        Collection $barangays,
        Collection $associations,
        Collection $memberTypes,
        Carbon $now,
    ): Collection {
        $audiences = ['all', 'barangay', 'association', 'member_type', 'all'];

        return collect(range(0, self::SAMPLE_COUNT - 1))->map(function (int $index) use ($admin, $barangays, $associations, $memberTypes, $audiences, $now): Advisory {
            $audience = $audiences[$index];
            $barangay = $barangays[$index % $barangays->count()];
            $association = $associations->get($barangay->id);

            $advisory = Advisory::query()->create([
                'title' => 'Sample Advisory ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'slug' => 'sample-advisory-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'content' => 'Sample advisory content for dashboard cards, mobile advisory pages, and filters.',
                'status' => $index % 4 === 0 ? 'draft' : 'published',
                'audience_type' => $audience,
                'barangay_id' => $audience === 'barangay' ? $barangay->id : null,
                'association_id' => $audience === 'association' ? $association?->id : null,
                'published_at' => $index % 4 === 0 ? null : $now->copy()->subDays($index + 1),
                'published_by' => $admin->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($audience === 'member_type') {
                $advisory->memberTypes()->sync([$memberTypes->values()[$index % $memberTypes->count()]->id]);
            }

            return $advisory;
        });
    }

    private function seedAdvisoryAttachments(Collection $advisories, Carbon $now): void
    {
        foreach ($advisories->values() as $index => $advisory) {
            DB::table('advisory_attachments')->insert([
                'advisory_id' => $advisory->id,
                'disk' => 'public',
                'path' => 'sample-advisories/attachment-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.pdf',
                'original_name' => 'advisory-attachment-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 204800 + ($index * 1000),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedQueries(Collection $farmers, Carbon $now): Collection
    {
        $statuses = ['New', 'In Progress', 'New', 'Resolved', 'In Progress'];

        return collect(range(0, self::SAMPLE_COUNT - 1))->map(function (int $index) use ($farmers, $statuses, $now): Query {
            $status = $statuses[$index];

            return Query::query()->create([
                'farmer_id' => $farmers[$index]->id,
                'subject' => 'Sample Query ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'message' => 'This is a sample concern submitted by the farmer for query handling tests.',
                'status' => $status,
                'submitted_at' => $now->copy()->subDays(9 - min($index, 8)),
                'closed_at' => $status === 'closed' ? $now->copy()->subDays(5 - min($index, 4)) : null,
                'archived_at' => $index === self::SAMPLE_COUNT - 1 ? $now->copy()->subDay() : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function seedQueryResponses(Collection $queries, Collection $staffUsers, Carbon $now): Collection
    {
        return $queries->values()->map(function (Query $query, int $index) use ($staffUsers, $now): QueryResponse {
            return QueryResponse::query()->create([
                'query_id' => $query->id,
                'responded_by' => $staffUsers[$index % $staffUsers->count()]->id,
                'message' => 'Sample response for the submitted farmer query.',
                'responded_at' => in_array($query->status, ['In Progress', 'Resolved'], true)
                    ? $now->copy()->subDays(4 - min($index, 3))
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function seedQueryImages(Collection $queries, Carbon $now): void
    {
        foreach ($queries->values() as $index => $query) {
            DB::table('query_images')->insert([
                'query_id' => $query->id,
                'disk' => 'public',
                'path' => 'sample-queries/query-image-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
                'original_name' => 'query-image-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 307200 + ($index * 1200),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedQueryResponseAttachments(Collection $responses, Carbon $now): void
    {
        foreach ($responses->values() as $index => $response) {
            DB::table('query_response_attachments')->insert([
                'query_response_id' => $response->id,
                'disk' => 'public',
                'path' => 'sample-query-responses/response-attachment-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.pdf',
                'original_name' => 'response-attachment-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 153600 + ($index * 600),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedNotifications(User $admin, Carbon $now): Collection
    {
        $types = NotificationType::cases();

        return collect(range(0, self::SAMPLE_COUNT - 1))->map(function (int $index) use ($types, $admin, $now) {
            $type = $types[$index % count($types)];
            $status = $index % 3 === 0 ? 'sent' : 'queued';

            $record = [
                'type' => $type->value,
                'channel' => 'database',
                'subject' => 'Sample ' . $type->label() . ' ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'message' => 'Sample notification message for ' . Str::headline($type->value) . '.',
                'payload' => json_encode([
                    'sample_batch' => self::SAMPLE_BATCH,
                    'sequence' => $index + 1,
                ], JSON_THROW_ON_ERROR),
                'status' => $status,
                'queued_at' => $now->copy()->subDays(3 - min($index, 2)),
                'sent_at' => $status === 'sent' ? $now->copy()->subDays(2 - min($index, 1)) : null,
                'created_by' => $admin->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $id = DB::table('notifications')->insertGetId($record);

            return (object) array_merge(['id' => $id], $record);
        });
    }

    private function seedNotificationRecipients(
        Collection $notifications,
        Collection $farmers,
        Collection $farmerUsers,
        Carbon $now,
    ): void {
        $statuses = ['delivered', 'pending', 'delivered', 'failed', 'delivered'];

        foreach ($notifications->values() as $index => $notification) {
            $status = $statuses[$index];

            DB::table('notification_recipients')->insert([
                'notification_id' => $notification->id,
                'user_id' => $farmerUsers[$index % $farmerUsers->count()]->id,
                'farmer_id' => $farmers[$index % $farmers->count()]->id,
                'recipient_address' => $farmers[$index % $farmers->count()]->email,
                'status' => $status,
                'delivered_at' => $status === 'delivered' ? $now->copy()->subHours($index + 1) : null,
                'read_at' => $status === 'delivered' && $index % 3 === 0 ? $now->copy()->subHours($index + 2) : null,
                'failed_at' => $status === 'failed' ? $now->copy()->subHours($index + 1) : null,
                'failure_reason' => $status === 'failed' ? 'Sample delivery failure for testing.' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedPaymongoWebhookEvents(
        Collection $assessments,
        Collection $applications,
        Collection $renewals,
        Collection $payments,
        Carbon $now,
    ): void {
        foreach (range(0, self::SAMPLE_COUNT - 1) as $index) {
            DB::table('paymongo_webhook_events')->insert([
                'paymongo_event_id' => 'evt_sample_' . Str::padLeft((string) ($index + 1), 6, '0'),
                'event_type' => $index % 2 === 0 ? 'payment.paid' : 'payment.failed',
                'resource_id' => 'res_' . Str::padLeft((string) ($index + 1), 6, '0'),
                'resource_type' => 'payment',
                'livemode' => false,
                'status' => $index % 2 === 0 ? 'processed' : 'received',
                'message' => 'Sample PayMongo webhook payload for payment testing.',
                'reference_no' => $payments[$index]->reference_no,
                'payment_assessment_id' => $assessments[$index]->id,
                'membership_application_id' => $index < self::APPLICATION_SAMPLE_COUNT ? $applications[$index]->id : null,
                'renewal_request_id' => $index >= self::APPLICATION_SAMPLE_COUNT ? $renewals[$index]->id : null,
                'payment_id' => $payments[$index]->id,
                'amount' => $payments[$index]->amount_paid,
                'payload' => json_encode([
                    'sample_batch' => self::SAMPLE_BATCH,
                    'payment_id' => $payments[$index]->id,
                    'assessment_id' => $assessments[$index]->id,
                ], JSON_THROW_ON_ERROR),
                'processed_at' => $index % 2 === 0 ? $now->copy()->subHours($index + 1) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedPasswordResetOtps(Collection $farmerUsers, Collection $staffUsers, Carbon $now): void
    {
        foreach ($farmerUsers->values() as $index => $user) {
            DB::table('farmer_password_reset_otps')->insert([
                'user_id' => $user->id,
                'code_hash' => Hash::make('123456'),
                'expires_at' => $now->copy()->addMinutes(15 + $index),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $sampleOfficeUsers = $staffUsers
            ->filter(fn (User $user): bool => str_ends_with($user->email, '@sample.anitech.test'))
            ->values();

        foreach ($sampleOfficeUsers->values() as $index => $user) {
            DB::table('office_password_reset_otps')->insert([
                'user_id' => $user->id,
                'code_hash' => Hash::make('654321'),
                'expires_at' => $now->copy()->addMinutes(20 + $index),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
