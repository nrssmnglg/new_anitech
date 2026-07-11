<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenewalReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_reminders_five_days_before_the_active_deadline_without_duplicates(): void
    {
        Carbon::setTestNow('2026-04-03 08:00:00');

        FeeSchedule::query()->create([
            'year' => 2026,
            'membership_fee' => 0,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'renewal_deadline' => '2026-04-08',
            'is_active' => true,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ]);

        $memberType = MemberType::query()->create([
            'code' => 'OM',
            'name' => 'Old Member',
            'is_new_member' => false,
            'is_senior' => false,
            'requires_membership_fee' => false,
            'mortuary_eligible' => true,
        ]);

        $barangay = Barangay::query()->create([
            'name' => 'San Isidro',
            'code' => 'BRGY-REMINDER',
            'status' => 'active',
        ]);

        $dueFarmer = Farmer::query()->create([
            'farmer_code' => 'FRM-REM-001',
            'first_name' => 'Due',
            'last_name' => 'Farmer',
            'email' => 'due@example.test',
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'status' => 'active',
            'membership_status' => 'active',
            'last_renewal_year' => 2025,
        ]);

        User::query()->create([
            'name' => 'Due Farmer',
            'email' => 'due@example.test',
            'password' => 'secret12345',
            'status' => 'active',
            'farmer_id' => $dueFarmer->id,
        ]);

        Farmer::query()->create([
            'farmer_code' => 'FRM-REM-002',
            'first_name' => 'Renewed',
            'last_name' => 'Farmer',
            'email' => 'renewed@example.test',
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'status' => 'active',
            'membership_status' => 'active',
            'last_renewal_year' => 2026,
        ]);

        Farmer::query()->create([
            'farmer_code' => 'FRM-REM-003',
            'first_name' => 'Inactive',
            'last_name' => 'Farmer',
            'email' => 'inactive@example.test',
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'status' => 'inactive',
            'membership_status' => 'active',
            'last_renewal_year' => 2025,
        ]);

        $this->artisan('app:send-renewal-reminders --year=2026')
            ->expectsOutput('Queued 1 renewal reminder(s) for 2026.')
            ->assertExitCode(0);

        $notificationId = DB::table('notifications')
            ->where('type', 'renewal_reminder')
            ->value('id');

        $this->assertNotNull($notificationId);
        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notificationId,
            'farmer_id' => $dueFarmer->id,
        ]);

        $this->assertSame(
            1,
            DB::table('notification_recipients')
                ->join('notifications', 'notifications.id', '=', 'notification_recipients.notification_id')
                ->where('notifications.type', 'renewal_reminder')
                ->count(),
        );

        $this->artisan('app:send-renewal-reminders --year=2026')
            ->expectsOutput('No farmers are due for renewal reminders for 2026.')
            ->assertExitCode(0);

        $this->assertSame(
            1,
            DB::table('notification_recipients')
                ->join('notifications', 'notifications.id', '=', 'notification_recipients.notification_id')
                ->where('notifications.type', 'renewal_reminder')
                ->count(),
        );

        Carbon::setTestNow();
    }
}
