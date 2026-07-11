<?php

namespace Tests\Feature;

use App\Models\Advisory;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchFourPublicUuidRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_four_models_generate_expected_admin_urls(): void
    {
        $advisory = Advisory::query()->create([
            'title' => 'Weather Alert',
            'slug' => 'weather-alert',
            'content' => 'Heavy rain advisory.',
            'status' => 'draft',
            'audience_type' => 'all',
        ]);
        $user = User::query()->create([
            'name' => 'Office Admin',
            'email' => 'reports@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $reportExport = ReportExport::query()->create([
            'report_name' => 'renewal_summary',
            'requested_by' => $user->id,
            'filters' => ['year' => now()->year],
            'disk' => 'public',
            'path' => 'reports/renewal-summary.pdf',
            'file_name' => 'renewal-summary.pdf',
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $this->assertStringEndsWith('/reports/exports/' . $reportExport->uuid . '/download', route('admin.reports.exports.download', $reportExport));
        $this->assertStringEndsWith('/advisories/' . $advisory->uuid, route('admin.advisories.show', ['advisory' => $advisory->uuid], false));
        $this->assertStringEndsWith('/advisories/' . $advisory->uuid . '/edit', route('admin.advisories.edit', ['advisory' => $advisory->uuid], false));
        $this->assertStringEndsWith('/advisories/' . $advisory->uuid . '/publish', route('admin.advisories.publish', ['advisory' => $advisory->uuid], false));
        $this->assertStringEndsWith('/advisories/' . $advisory->uuid . '/unpublish', route('admin.advisories.unpublish', ['advisory' => $advisory->uuid], false));
        $this->assertStringEndsWith('/farmer/advisories/' . $advisory->slug, route('farmer.pwa.advisories.show', $advisory->slug, false));
    }
}
