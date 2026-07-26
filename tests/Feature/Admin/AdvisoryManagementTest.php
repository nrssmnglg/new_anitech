<?php

namespace Tests\Feature\Admin;

use App\Models\Advisory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvisoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_create_advisory_with_attachments(): void
    {
        Storage::fake('public');

        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin.advisory@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        $attachment = UploadedFile::fake()->create('advisory-notice.pdf', 256, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('admin.advisories.store'), [
            'title' => 'Weather Advisory',
            'content' => 'Heavy rainfall expected over the next 24 hours.',
            'audience_type' => 'all',
            'attachments' => [$attachment],
        ]);

        $advisory = Advisory::query()->where('title', 'Weather Advisory')->firstOrFail();
        $attachmentRecord = $advisory->attachments()->firstOrFail();

        $response->assertRedirect(route('admin.advisories.show', $advisory));
        $response->assertSessionHas('success', 'Advisory draft created.');

        $this->assertDatabaseHas('attachments', [
            'module_type' => $advisory->getMorphClass(),
            'module_id' => $advisory->id,
            'original_name' => 'advisory-notice.pdf',
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('public')->assertExists($attachmentRecord->file_path);
    }
}
