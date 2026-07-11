<?php

namespace Tests\Feature\Admin;

use App\Models\OfficePasswordResetOtp;
use App\Models\User;
use App\Notifications\OfficePasswordResetOtpNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OfficeStaffAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_wrong_password_shows_a_password_specific_error_for_office_accounts(): void
    {
        $staff = User::query()->create([
            'name' => 'Office Staff',
            'email' => 'office.staff@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'office.staff@example.test',
            'password' => 'wrong-secret',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'password' => 'Incorrect password. You have 2 attempts remaining before a 15-minute lockout.',
        ]);
        $this->assertGuest();
    }

    public function test_office_portal_is_locked_after_three_failed_password_attempts(): void
    {
        $staff = User::query()->create([
            'name' => 'Office Staff',
            'email' => 'locked.office@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->from(route('login'))->post(route('login.attempt'), [
                'email' => 'locked.office@example.test',
                'password' => 'wrong-secret',
            ]);
        }

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'locked.office@example.test',
            'password' => 'wrong-secret',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'password' => 'Too many failed login attempts. Please try again in 15 minutes or use Forgot password.',
        ]);
        $this->assertGuest();
    }

    public function test_inactive_staff_cannot_log_into_the_office_portal(): void
    {
        $staff = User::query()->create([
            'name' => 'Inactive Staff',
            'email' => 'inactive.staff@example.test',
            'password' => 'secret123',
            'status' => 'inactive',
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'inactive.staff@example.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'This account is inactive.',
        ]);
        $this->assertGuest();
    }

    public function test_office_staff_can_request_a_password_reset_otp(): void
    {
        Notification::fake();

        $staff = User::query()->create([
            'name' => 'Office Staff',
            'email' => 'office.staff@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'office.staff@example.test',
        ]);

        $response->assertRedirect(route('password.reset', ['email' => 'office.staff@example.test']));
        $response->assertSessionHas('status');

        Notification::assertSentTo($staff, OfficePasswordResetOtpNotification::class);
        $this->assertDatabaseCount('office_password_reset_otps', 1);
    }

    public function test_office_staff_must_verify_otp_before_opening_new_password_form(): void
    {
        $staff = User::query()->create([
            'name' => 'Office Staff',
            'email' => 'verify.office@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        $this->get(route('password.create', ['email' => $staff->email]))
            ->assertRedirect(route('password.reset', ['email' => $staff->email]));
    }

    public function test_staff_with_forced_password_change_is_redirected_until_password_is_updated(): void
    {
        $staff = User::query()->create([
            'name' => 'New Staff',
            'email' => 'new.staff@example.test',
            'password' => 'secret123',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        $loginResponse = $this->post(route('login.attempt'), [
            'email' => 'new.staff@example.test',
            'password' => 'secret123',
        ]);

        $loginResponse->assertRedirect(route('admin.password.edit'));
        $this->assertAuthenticatedAs($staff);
        $this->assertNotNull($staff->fresh()->last_login_at);

        $this->get(route('admin.farmers.index'))
            ->assertRedirect(route('admin.password.edit'));

        $updateResponse = $this->actingAs($staff)->put(route('admin.password.update'), [
            'current_password' => 'secret123',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $updateResponse->assertRedirect(route('admin.farmers.index'));
        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertTrue(password_verify('newsecret123', $staff->fresh()->getAuthPassword()));

        $this->actingAs($staff)
            ->get(route('admin.farmers.index'))
            ->assertOk();
    }

    public function test_password_reset_clears_forced_change_flag_for_office_accounts(): void
    {
        $staff = User::query()->create([
            'name' => 'Reset Staff',
            'email' => 'reset.staff@example.test',
            'password' => 'secret123',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        OfficePasswordResetOtp::query()->create([
            'user_id' => $staff->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $verifyResponse = $this->post(route('password.verify'), [
            'email' => 'reset.staff@example.test',
            'otp' => '123456',
        ]);

        $verifyResponse->assertRedirect(route('password.create', ['email' => 'reset.staff@example.test']));

        $response = $this->withSession([
            'office_password_reset.verified_email' => 'reset.staff@example.test',
        ])->post(route('password.store'), [
            'email' => 'reset.staff@example.test',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertTrue(password_verify('newsecret123', $staff->fresh()->getAuthPassword()));
        $this->assertDatabaseCount('office_password_reset_otps', 0);
    }

    public function test_admin_created_staff_account_is_marked_for_password_reset_and_audited(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin.office@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Office Staff',
            'email' => 'office.staff@example.test',
            'role' => User::ROLE_STAFF,
            'status' => 'active',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $createdUser = User::query()->where('email', 'office.staff@example.test')->firstOrFail();

        $response->assertRedirect(route('admin.users.show', $createdUser));
        $this->assertSame($admin->id, $createdUser->created_by);
        $this->assertTrue($createdUser->must_change_password);
        $this->assertTrue($createdUser->hasRole(User::ROLE_STAFF));
    }

    public function test_admin_archives_user_account_instead_of_deleting_it(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin.archive@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        $staff = User::query()->create([
            'name' => 'Office Staff',
            'email' => 'staff.archive@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $staff->assignRole(User::ROLE_STAFF);

        $response = $this->actingAs($admin)->delete(route('admin.users.archive', $staff));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'User account archived.');
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'users',
            'event' => 'user_archived',
            'actor_user_id' => $admin->id,
            'subject_id' => $staff->id,
        ]);
    }
}
