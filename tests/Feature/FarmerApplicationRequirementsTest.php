<?php

namespace Tests\Feature;

use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmerApplicationRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_checklist_reflects_saved_requirements_and_subsequent_changes(): void
    {
        DocumentRequirement::query()->delete();
        $type = DocumentType::query()->create([
            'code' => 'custom_membership_certificate',
            'name' => 'Custom Membership Certificate',
            'status' => 'Active',
        ]);
        $requirement = DocumentRequirement::query()->create([
            'transaction_type' => 'Application',
            'document_type_id' => $type->id,
            'is_active' => true,
            'is_required' => true,
        ]);

        $this->getJson(route('farmer.pwa.application.requirements'))
            ->assertOk()->assertExactJson(['data' => ['Custom Membership Certificate']]);
        $this->withoutVite()->get('/farmer/app/apply')->assertOk()
            ->assertViewHas('shell', fn ($shell) => $shell['publicData']['applicationDocumentChecklist'] === ['Custom Membership Certificate']);

        $requirement->update(['is_required' => false]);
        $this->getJson(route('farmer.pwa.application.requirements'))
            ->assertOk()->assertExactJson(['data' => []]);

        $requirement->update(['is_required' => true, 'is_active' => false]);
        $this->getJson(route('farmer.pwa.application.requirements'))
            ->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_unconfigured_application_uses_mobile_defaults(): void
    {
        DocumentRequirement::query()->delete();
        $this->getJson(route('farmer.pwa.application.requirements'))->assertOk()
            ->assertExactJson(['data' => ['Birth Certificate', 'Cedula', '2x2 Picture']]);
    }
}
