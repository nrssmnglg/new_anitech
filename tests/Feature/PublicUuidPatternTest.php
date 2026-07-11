<?php

namespace Tests\Feature;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicUuidPatternTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('uuid_pattern_records', function ($table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->timestamps();
        });

    }

    public function test_shared_public_uuid_pattern_generates_route_urls_and_binds_by_uuid(): void
    {
        Route::middleware('web')->get('/_test/public-uuid/{record}', function (UuidPatternRecord $record) {
            return response()->json([
                'id' => $record->id,
                'uuid' => $record->uuid,
                'name' => $record->name,
            ]);
        })->name('test.public-uuid.show');
        app('router')->getRoutes()->refreshNameLookups();

        $record = UuidPatternRecord::query()->create([
            'name' => 'UUID Pattern',
        ]);

        $this->assertNotNull($record->uuid);
        $this->assertSame(route('test.public-uuid.show', $record), url('/_test/public-uuid/' . $record->uuid));

        $response = $this->get(route('test.public-uuid.show', $record));

        $response->assertOk();
        $response->assertJson([
            'id' => $record->id,
            'uuid' => $record->uuid,
            'name' => 'UUID Pattern',
        ]);
    }
}

class UuidPatternRecord extends Model
{
    use HasPublicUuid;

    protected $table = 'uuid_pattern_records';

    protected $fillable = [
        'uuid',
        'name',
    ];
}
