<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerDocument;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FarmerPwaApplicationApiResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_application_submit_validation_errors_return_json_for_pwa_requests(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-' . uniqid(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson(route('farmer.pwa.application.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birth_date' => now()->addDay()->toDateString(),
            'barangay_id' => $barangay->id,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('application/json', (string) $response->headers->get('content-type'));
        $response->assertJsonValidationErrors(['birth_date']);
    }

    public function test_application_submit_duplicate_rejection_returns_json_for_pwa_requests(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-' . uniqid(),
            'status' => 'active',
        ]);

        $memberType = MemberType::query()->create([
            'code' => 'NM',
            'name' => 'New Member',
            'is_new_member' => true,
            'is_senior' => false,
            'requires_membership_fee' => true,
            'mortuary_eligible' => true,
        ]);

        Farmer::query()->create([
            'farmer_code' => 'FRM-' . now()->format('Y') . '-00001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1990-01-01',
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson(route('farmer.pwa.application.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1990-01-01',
            'barangay_id' => $barangay->id,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('application/json', (string) $response->headers->get('content-type'));
        $response->assertJsonValidationErrors(['application']);
    }

    public function test_batch_document_upload_returns_json_for_pwa_requests(): void
    {
        Storage::fake('public');

        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-' . uniqid(),
            'status' => 'active',
        ]);

        $memberType = MemberType::query()->create([
            'code' => 'NM',
            'name' => 'New Member',
            'is_new_member' => true,
            'is_senior' => false,
            'requires_membership_fee' => true,
            'mortuary_eligible' => true,
        ]);

        $farmer = Farmer::query()->create([
            'farmer_code' => 'FRM-' . now()->format('Y') . '-00002',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '1990-02-02',
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'membership_status' => MembershipStatus::PENDING_DOCUMENTS->value,
            'registered_at' => now(),
        ]);

        $application = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id,
            'application_no' => 'AT-' . strtoupper(substr(uniqid(), -8)),
            'source' => 'mobile',
            'status' => ApplicationStatus::SUBMITTED->value,
            'submitted_at' => now(),
        ]);

        foreach ([
            DocumentType::BIRTH_CERTIFICATE,
            DocumentType::CEDULA,
            DocumentType::TWO_BY_TWO_PICTURE,
        ] as $type) {
            FarmerDocument::query()->create([
                'farmer_id' => $farmer->id,
                'membership_application_id' => $application->id,
                'document_type' => $type->value,
                'disk' => 'pending',
                'path' => 'pending-upload/' . $type->value,
                'original_name' => 'Pending Upload',
                'mime_type' => null,
                'file_size' => 0,
                'is_required' => true,
                'is_received' => false,
                'verification_status' => DocumentVerificationStatus::PENDING->value,
            ]);
        }

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post(route('farmer.pwa.application.documents.store', ['applicationNo' => $application->application_no]), [
            'birth_date' => '1990-02-02',
            'documents' => [
                DocumentType::BIRTH_CERTIFICATE->value => UploadedFile::fake()->create('birth-certificate.jpg', 120, 'image/jpeg'),
                DocumentType::CEDULA->value => UploadedFile::fake()->create('cedula.jpg', 120, 'image/jpeg'),
                DocumentType::TWO_BY_TWO_PICTURE->value => UploadedFile::fake()->create('two-by-two.jpg', 120, 'image/jpeg'),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Documents uploaded successfully.');

        $documents = FarmerDocument::query()
            ->where('membership_application_id', $application->id)
            ->get();

        $this->assertCount(3, $documents);
        foreach ($documents as $document) {
            $this->assertSame('public', $document->disk);
            $this->assertNotNull($document->path);
            Storage::disk('public')->assertExists($document->path);
        }
    }
}
