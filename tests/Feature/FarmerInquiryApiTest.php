<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\Query;
use App\Models\QueryCategory;
use App\Models\QueryResponse;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FarmerInquiryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_view_only_own_inquiry_thread(): void
    {
        $owner = Farmer::factory()->create();
        $other = Farmer::factory()->create();

        $ownerUser = User::factory()->create(['farmer_id' => $owner->id]);
        User::factory()->create(['farmer_id' => $other->id]);

        $category = QueryCategory::query()->create([
            'code' => 'OTHER',
            'name' => 'Other',
            'description' => 'Other',
            'status' => 'Active',
        ]);

        $ownQuery = Query::query()->create([
            'farmer_id' => $owner->id,
            'category_id' => $category->id,
            'subject' => 'Own inquiry',
            'message' => 'Owner message',
            'status' => 'New',
            'created_at' => now(),
        ]);

        $otherQuery = Query::query()->create([
            'farmer_id' => $other->id,
            'category_id' => $category->id,
            'subject' => 'Other inquiry',
            'message' => 'Other message',
            'status' => 'New',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($ownerUser, ['*'], 'farmer_pwa');

        $this->getJson('/api/farmer/inquiries/' . $ownQuery->getRouteKey())
            ->assertOk()
            ->assertJsonPath('data.subject', 'Own inquiry');

        $this->getJson('/api/farmer/inquiries/' . $otherQuery->getRouteKey())
            ->assertForbidden();
    }

    public function test_thread_last_activity_uses_latest_response_even_if_from_farmer(): void
    {
        $farmer = Farmer::factory()->create();
        $user = User::factory()->create(['farmer_id' => $farmer->id]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $category = QueryCategory::query()->create([
            'code' => 'OTHER',
            'name' => 'Other',
            'description' => 'Other',
            'status' => 'Active',
        ]);

        $query = Query::query()->create([
            'farmer_id' => $farmer->id,
            'category_id' => $category->id,
            'subject' => 'Thread inquiry',
            'message' => 'Initial message',
            'status' => 'In Progress',
            'created_at' => now()->subDays(2),
        ]);

        QueryResponse::query()->create([
            'query_id' => $query->id,
            'responded_by' => $staff->id,
            'message' => 'Staff reply',
            'responded_at' => now()->subDay(),
        ]);

        QueryResponse::query()->create([
            'query_id' => $query->id,
            'responded_by' => $user->id,
            'message' => 'Farmer follow-up',
            'responded_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*'], 'farmer_pwa');

        $response = $this->getJson('/api/farmer/inquiries/' . $query->getRouteKey())
            ->assertOk()
            ->assertJsonPath('data.last_staff_reply_excerpt', 'Staff reply')
            ->assertJsonPath('data.responses.1.message', 'Farmer follow-up');

        $lastActivity = $response->json('data.last_activity_at');
        $latestResponseAt = QueryResponse::query()
            ->where('query_id', $query->id)
            ->latest('responded_at')
            ->firstOrFail()
            ->responded_at;

        $this->assertSame(
            $latestResponseAt->format('Y-m-d H:i'),
            now()->parse($lastActivity)->format('Y-m-d H:i')
        );
    }

    public function test_thread_marks_mime_based_images_as_previewable_even_without_filename_extension(): void
    {
        Storage::fake('public');

        $farmer = Farmer::factory()->create();
        $user = User::factory()->create(['farmer_id' => $farmer->id]);

        $category = QueryCategory::query()->create([
            'code' => 'OTHER',
            'name' => 'Other',
            'description' => 'Other',
            'status' => 'Active',
        ]);

        $query = Query::query()->create([
            'farmer_id' => $farmer->id,
            'category_id' => $category->id,
            'subject' => 'Attachment inquiry',
            'message' => 'See attachment',
            'status' => 'New',
            'created_at' => now(),
        ]);

        $storedPath = UploadedFile::fake()->image('camera-upload.jpg')->store('queries/' . $query->id . '/attachments', 'public');

        $query->attachments()->create([
            'original_name' => 'image-jpg',
            'mime_type' => 'image/jpeg',
            'file_path' => $storedPath,
            'uploaded_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*'], 'farmer_pwa');

        $this->getJson('/api/farmer/inquiries/' . $query->getRouteKey())
            ->assertOk()
            ->assertJsonPath('data.attachments.0.name', 'image-jpg')
            ->assertJsonPath('data.attachments.0.mime_type', 'image/jpeg')
            ->assertJsonPath('data.attachments.0.is_image', true)
            ->assertJsonPath('data.attachments.0.can_preview', true);
    }
}
