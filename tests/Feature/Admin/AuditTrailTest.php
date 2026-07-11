<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_successful_office_login_creates_an_audit_log(): void
    {
        $staff = User::query()->create([
            'name' => 'Office Staff',
            'email' => 'office.staff@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        $response = $this->post(route('login.attempt'), [
            'email' => 'office.staff@example.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.farmers.index'));
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'auth',
            'event' => 'login_succeeded',
            'actor_user_id' => $staff->id,
        ]);
    }

    public function test_admin_can_view_audit_trail_page(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        AuditLog::query()->create([
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_role' => $admin->role,
            'module' => 'users',
            'event' => 'user_created',
            'description' => 'Created a user account.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit Trail')
            ->assertSee('Created a user account.');
    }
}
