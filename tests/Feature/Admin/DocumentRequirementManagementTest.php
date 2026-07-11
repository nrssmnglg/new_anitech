<?php

namespace Tests\Feature\Admin;

use App\Enums\DocumentType;
use App\Models\DocumentRequirement;
use App\Models\User;
use App\Services\Documents\DocumentRequirementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRequirementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_add_membership_requirement_from_admin_page(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin.requirement@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        $response = $this->actingAs($admin)->post(route('admin.document-requirements.store'), [
            'document_type' => DocumentType::GOVERNMENT_ID->value,
        ]);

        $response->assertRedirect(route('admin.document-requirements.index'));
        $response->assertSessionHas('success', 'Membership requirement added.');

        $required = app(DocumentRequirementService::class)->requiredFor('application');

        $this->assertContains(DocumentType::GOVERNMENT_ID, $required);
        $this->assertDatabaseHas('document_requirements', [
            'workflow' => 'application',
            'document_type' => DocumentType::GOVERNMENT_ID->value,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_membership_requirement_from_admin_page(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin.requirement.update@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        $requirement = DocumentRequirement::query()->where('workflow', 'application')->where('document_type', DocumentType::CEDULA->value)->firstOrFail();

        $response = $this->actingAs($admin)->put(route('admin.document-requirements.update', $requirement), [
            'document_type' => DocumentType::GOVERNMENT_ID->value,
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('admin.document-requirements.index'));
        $response->assertSessionHas('success', 'Membership requirement updated.');
        $this->assertDatabaseHas('document_requirements', [
            'id' => $requirement->id,
            'document_type' => DocumentType::GOVERNMENT_ID->value,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_membership_requirement_from_admin_page(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin.requirement.delete@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        $requirement = DocumentRequirement::query()->where('workflow', 'application')->where('document_type', DocumentType::CEDULA->value)->firstOrFail();

        $response = $this->actingAs($admin)->delete(route('admin.document-requirements.destroy', $requirement));

        $response->assertRedirect(route('admin.document-requirements.index'));
        $response->assertSessionHas('success', 'Membership requirement deleted.');
        $this->assertDatabaseMissing('document_requirements', [
            'id' => $requirement->id,
        ]);
    }
}
