<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MemberTypeCode;
use App\Enums\MembershipApplicationRejectionReason;
use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerDocument;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RejectedMobileMembershipApplicationWithAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $barangay = Barangay::query()->updateOrCreate(
                ['code' => 'BRGY02'],
                [
                    'name' => 'AGDAO',
                    'status' => 'active',
                ],
            );

            $association = Association::query()->updateOrCreate(
                ['code' => 'ASSOC02'],
                [
                    'barangay_id' => $barangay->id,
                    'name' => 'AGDAO FARMERS ASSOCIATION',
                    'status' => 'active',
                ],
            );

            $memberType = MemberType::query()->updateOrCreate(
                ['code' => MemberTypeCode::NM->value],
                [
                    'name' => MemberTypeCode::NM->label(),
                    'is_new_member' => true,
                    'is_senior' => false,
                    'requires_membership_fee' => true,
                    'mortuary_eligible' => true,
                ],
            );

            Role::findOrCreate(User::ROLE_ADMIN, 'web');
            Role::findOrCreate(User::ROLE_FARMER, 'web');

            $reviewer = User::query()->updateOrCreate(
                ['email' => 'admin@anitech.test'],
                [
                    'name' => 'AniTech Admin',
                    'password' => 'password123',
                    'status' => 'active',
                    'farmer_id' => null,
                    'created_by' => null,
                    'must_change_password' => false,
                    'last_login_at' => Carbon::create(2026, 4, 18, 9, 30, 0),
                    'last_login_ip' => '192.168.10.10',
                ],
            );

            if (! $reviewer->hasRole(User::ROLE_ADMIN)) {
                $reviewer->syncRoles([User::ROLE_ADMIN]);
            }

            $farmer = Farmer::query()->updateOrCreate(
                ['farmer_code' => 'FRM-2026-00991'],
                [
                    'first_name' => 'Cristina',
                    'middle_name' => 'Morales',
                    'last_name' => 'Lazaro',
                    'suffix' => null,
                    'sex' => 'Female',
                    'birth_date' => '1991-08-12',
                    'civil_status' => 'Married',
                    'mobile_number' => '09181234567',
                    'email' => 'cristina.lazaro@gmail.com',
                    'address' => 'Purok 3, Agdao, San Carlos City, Pangasinan',
                    'barangay_id' => $barangay->id,
                    'association_id' => $association->id,
                    'member_type_id' => $memberType->id,
                    'is_registry_record' => false,
                    'record_origin' => 'application',
                    'source_application_id' => null,
                    'status' => FarmerStatus::PENDING->value,
                    'membership_status' => MembershipStatus::PENDING_APPLICATION->value,
                    'registered_at' => Carbon::create(2026, 4, 10, 8, 45, 0),
                    'activated_at' => null,
                    'last_renewal_year' => null,
                    'inactive_at' => null,
                    'inactive_reason' => null,
                    'remarks' => 'Rejected mobile membership application test record.',
                ],
            );

            $application = MembershipApplication::query()->updateOrCreate(
                ['application_no' => 'MOB-APP-2026-00091'],
                [
                    'farmer_id' => $farmer->id,
                    'source' => 'mobile',
                    'status' => ApplicationStatus::REJECTED->value,
                    'submitted_at' => Carbon::create(2026, 4, 10, 9, 0, 0),
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => Carbon::create(2026, 4, 12, 14, 15, 0),
                    'approved_at' => null,
                    'remarks' => 'Applicant must upload a clearer government ID and updated barangay certification.',
                    'rejection_reason' => MembershipApplicationRejectionReason::MISSING_DOCUMENTS->value,
                    'rejection_details' => 'The uploaded government ID is unreadable and the barangay certification does not match the current address.',
                ],
            );

            $farmer->forceFill([
                'source_application_id' => $application->id,
            ])->save();

            $documents = [
                [
                    'document_type' => DocumentType::APPLICATION_FORM,
                    'verification_status' => DocumentVerificationStatus::REJECTED,
                    'original_name' => 'application-form.pdf',
                    'remarks' => 'Application rejected together with the submission.',
                ],
                [
                    'document_type' => DocumentType::GOVERNMENT_ID,
                    'verification_status' => DocumentVerificationStatus::REJECTED,
                    'original_name' => 'government-id.jpg',
                    'remarks' => 'Government ID image is blurred.',
                ],
                [
                    'document_type' => DocumentType::BARANGAY_CERTIFICATION,
                    'verification_status' => DocumentVerificationStatus::REJECTED,
                    'original_name' => 'barangay-certification.pdf',
                    'remarks' => 'Barangay certification shows an old address.',
                ],
                [
                    'document_type' => DocumentType::PROOF_OF_FARMING,
                    'verification_status' => DocumentVerificationStatus::PENDING,
                    'original_name' => 'proof-of-farming.pdf',
                    'remarks' => null,
                ],
            ];

            foreach ($documents as $index => $document) {
                FarmerDocument::query()->updateOrCreate(
                    [
                        'farmer_id' => $farmer->id,
                        'membership_application_id' => $application->id,
                        'document_type' => $document['document_type']->value,
                    ],
                    [
                        'renewal_request_id' => null,
                        'disk' => 'public',
                        'path' => 'membership-applications/' . $application->application_no . '/' . $document['document_type']->value . '/' . $document['original_name'],
                        'original_name' => $document['original_name'],
                        'mime_type' => str_ends_with($document['original_name'], '.jpg') ? 'image/jpeg' : 'application/pdf',
                        'file_size' => 180000 + ($index * 5000),
                        'is_required' => true,
                        'is_received' => true,
                        'received_by' => null,
                        'received_at' => Carbon::create(2026, 4, 10, 9, 15 + $index, 0),
                        'verification_status' => $document['verification_status']->value,
                        'verified_by' => $document['verification_status'] !== DocumentVerificationStatus::PENDING ? $reviewer->id : null,
                        'verified_at' => $document['verification_status'] !== DocumentVerificationStatus::PENDING
                            ? Carbon::create(2026, 4, 12, 14, 20 + $index, 0)
                            : null,
                        'remarks' => $document['remarks'],
                    ],
                );
            }

            $user = User::query()->updateOrCreate(
                ['email' => 'cristina.lazaro@gmail.com'],
                [
                    'name' => $farmer->full_name,
                    'password' => 'farmer12345',
                    'status' => 'active',
                    'must_change_password' => false,
                    'farmer_id' => $farmer->id,
                    'created_by' => null,
                    'last_login_at' => Carbon::create(2026, 4, 13, 19, 20, 0),
                    'last_login_ip' => '10.0.0.45',
                ],
            );

            if (! $user->hasRole(User::ROLE_FARMER)) {
                $user->syncRoles([User::ROLE_FARMER]);
            }
        });
    }
}
