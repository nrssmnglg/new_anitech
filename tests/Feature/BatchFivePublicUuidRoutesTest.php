<?php

namespace Tests\Feature;

use App\Models\Advisory;
use App\Models\AdvisoryAttachment;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerDocument;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\Query;
use App\Models\QueryImage;
use App\Models\QueryResponse;
use App\Models\QueryResponseAttachment;
use App\Models\RenewalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchFivePublicUuidRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_nested_resources_generate_uuid_based_urls(): void
    {
        $farmer = $this->makeFarmer();
        $query = Query::query()->create([
            'farmer_id' => $farmer->id,
            'subject' => 'Need help',
            'message' => 'Question body',
            'status' => 'open',
            'submitted_at' => now(),
        ]);
        $image = QueryImage::query()->create([
            'query_id' => $query->id,
            'disk' => 'public',
            'path' => 'queries/sample-image.jpg',
            'original_name' => 'sample-image.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1234,
        ]);
        $responder = User::query()->create([
            'name' => 'Responder',
            'email' => 'responder@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $response = QueryResponse::query()->create([
            'query_id' => $query->id,
            'responded_by' => $responder->id,
            'message' => 'Reply body',
            'responded_at' => now(),
        ]);
        $responseAttachment = QueryResponseAttachment::query()->create([
            'query_response_id' => $response->id,
            'disk' => 'public',
            'path' => 'queries/sample-attachment.pdf',
            'original_name' => 'sample-attachment.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
        ]);

        $advisory = Advisory::query()->create([
            'title' => 'Weather Alert',
            'slug' => 'weather-alert',
            'content' => 'Heavy rain advisory.',
            'status' => 'draft',
            'audience_type' => 'all',
        ]);
        $advisoryAttachment = AdvisoryAttachment::query()->create([
            'advisory_id' => $advisory->id,
            'disk' => 'public',
            'path' => 'advisories/weather-alert.pdf',
            'original_name' => 'weather-alert.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $application = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-00001',
            'source' => 'walk_in',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $applicationDocument = FarmerDocument::query()->create([
            'farmer_id' => $farmer->id,
            'membership_application_id' => $application->id,
            'document_type' => 'cedula',
            'disk' => 'public',
            'path' => 'documents/cedula.pdf',
            'original_name' => 'cedula.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'is_required' => true,
            'is_received' => true,
            'verification_status' => 'pending',
        ]);
        $renewal = RenewalRequest::query()->create([
            'farmer_id' => $farmer->id,
            'year' => now()->year,
            'source' => 'walk_in',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $renewalDocument = FarmerDocument::query()->create([
            'farmer_id' => $farmer->id,
            'renewal_request_id' => $renewal->id,
            'document_type' => 'previous_membership_id',
            'disk' => 'public',
            'path' => 'documents/previous-membership-id.pdf',
            'original_name' => 'previous-membership-id.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'is_required' => true,
            'is_received' => true,
            'verification_status' => 'pending',
        ]);

        $this->assertStringEndsWith('/queries/' . $query->uuid . '/images/' . $image->uuid, route('admin.queries.images.show', [$query, $image], false));
        $this->assertStringEndsWith('/farmer/queries/' . $query->uuid . '/images/' . $image->uuid, route('farmer.pwa.queries.images.show', [$query, $image], false));
        $this->assertStringEndsWith('/queries/' . $query->uuid . '/responses/' . $response->uuid . '/attachments/' . $responseAttachment->uuid, route('admin.queries.responses.attachments.show', [$query, $response, $responseAttachment], false));
        $this->assertStringEndsWith('/farmer/queries/' . $query->uuid . '/responses/' . $response->uuid . '/attachments/' . $responseAttachment->uuid, route('farmer.pwa.queries.responses.attachments.show', [$query, $response, $responseAttachment], false));
        $this->assertStringEndsWith('/advisories/' . $advisory->uuid . '/attachments/' . $advisoryAttachment->uuid, route('admin.advisories.attachments.show', ['advisory' => $advisory->uuid, 'attachment' => $advisoryAttachment], false));
        $this->assertStringEndsWith('/farmer/advisories/' . $advisory->slug . '/attachments/' . $advisoryAttachment->uuid, route('farmer.pwa.advisories.attachments.show', ['advisory' => $advisory->slug, 'attachment' => $advisoryAttachment], false));
        $this->assertStringEndsWith('/membership-applications/' . $application->uuid . '/documents/' . $applicationDocument->uuid . '/view', route('admin.membership-applications.documents.view', [$application, $applicationDocument], false));
        $this->assertStringEndsWith('/membership-applications/' . $application->uuid . '/documents/' . $applicationDocument->uuid . '/review', route('admin.membership-applications.documents.review', [$application, $applicationDocument], false));
        $this->assertStringEndsWith('/renewals/' . $renewal->uuid . '/documents/' . $renewalDocument->uuid, route('admin.renewals.documents.view', [$renewal, $renewalDocument], false));
        $this->assertStringEndsWith('/renewals/' . $renewal->uuid . '/documents/' . $renewalDocument->uuid . '/review', route('admin.renewals.documents.review', [$renewal, $renewalDocument], false));
    }

    private function makeFarmer(): Farmer
    {
        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-001',
            'status' => 'active',
        ]);
        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'San Roque Growers',
            'code' => 'ASSOC-001',
            'status' => 'active',
        ]);
        $memberType = MemberType::query()->create([
            'code' => 'OM',
            'name' => 'Old Member',
            'is_new_member' => false,
            'is_senior' => false,
            'requires_membership_fee' => false,
            'mortuary_eligible' => true,
        ]);

        return Farmer::query()->create([
            'farmer_code' => 'FRM-' . now()->format('Y') . '-00001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => 'active',
            'registered_at' => now(),
        ]);
    }
}
