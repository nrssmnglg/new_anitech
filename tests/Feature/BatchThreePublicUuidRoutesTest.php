<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Barangay;
use App\Models\DocumentRequirement;
use App\Models\FeeSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchThreePublicUuidRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_three_models_generate_uuid_based_urls(): void
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
        $feeSchedule = FeeSchedule::query()->create([
            'year' => now()->year,
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'renewal_deadline' => now()->toDateString(),
            'is_active' => true,
            'effective_from' => now()->toDateString(),
        ]);
        $requirement = DocumentRequirement::query()->create([
            'workflow' => 'application',
            'source' => null,
            'document_type' => 'cedula',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertStringEndsWith('/fee-schedules/' . $feeSchedule->uuid . '/edit', route('admin.fee-schedules.edit', $feeSchedule));
        $this->assertStringEndsWith('/document-requirements/' . $requirement->uuid, route('admin.document-requirements.update', $requirement, false));
        $this->assertStringEndsWith('/barangays/' . $barangay->uuid, route('admin.barangays.show', $barangay));
        $this->assertStringEndsWith('/barangays/' . $barangay->uuid . '/status', route('admin.barangays.status', $barangay));
        $this->assertStringEndsWith('/associations/' . $association->uuid, route('admin.associations.show', $association));
        $this->assertStringEndsWith('/associations/' . $association->uuid . '/status', route('admin.associations.status', $association));
    }
}
