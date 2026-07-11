<?php

namespace Tests\Feature;

use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerPasswordResetOtp;
use App\Models\MemberType;
use App\Models\User;
use App\Notifications\FarmerResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FarmerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_request_a_password_reset_otp(): void
    {
        Notification::fake();

        $user = $this->makeFarmerUser('farmer.reset@example.test');

        $response = $this->from(route('farmer.pwa.password.request'))->post(route('farmer.pwa.password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('farmer.pwa.password.reset', ['email' => $user->email]));
        $response->assertSessionHas('success');

        Notification::assertSentTo($user, FarmerResetPasswordNotification::class);
        $this->assertDatabaseCount('farmer_password_reset_otps', 1);
    }

    public function test_office_email_does_not_receive_farmer_reset_otp(): void
    {
        Notification::fake();

        $officeUser = User::query()->create([
            'name' => 'Office User',
            'email' => 'office@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $response = $this->from(route('farmer.pwa.password.request'))->post(route('farmer.pwa.password.email'), [
            'email' => $officeUser->email,
        ]);

        $response->assertRedirect(route('farmer.pwa.password.request'));
        $response->assertSessionHas('success');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('farmer_password_reset_otps', 0);
    }

    public function test_farmer_must_verify_otp_before_opening_new_password_form(): void
    {
        $user = $this->makeFarmerUser('farmer.verify@example.test');

        $this->get(route('farmer.pwa.password.create', ['email' => $user->email]))
            ->assertRedirect(route('farmer.pwa.password.reset', ['email' => $user->email]));
    }

    public function test_farmer_can_reset_password_with_valid_otp(): void
    {
        $user = $this->makeFarmerUser('farmer.reset2@example.test');

        FarmerPasswordResetOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $verifyResponse = $this->post(route('farmer.pwa.password.verify'), [
            'email' => $user->email,
            'otp' => '123456',
        ]);

        $verifyResponse->assertRedirect(route('farmer.pwa.password.create', ['email' => $user->email]));

        $response = $this->withSession([
            'farmer_password_reset.verified_email' => $user->email,
        ])->post(route('farmer.pwa.password.store'), [
            'email' => $user->email,
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $response->assertRedirect(route('farmer.pwa.login'));
        $response->assertSessionHas('success');
        $this->assertTrue(password_verify('newsecret123', $user->fresh()->getAuthPassword()));
        $this->assertDatabaseCount('farmer_password_reset_otps', 0);
    }

    public function test_farmer_cannot_verify_password_reset_with_invalid_otp(): void
    {
        $user = $this->makeFarmerUser('farmer.reset3@example.test');

        FarmerPasswordResetOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->from(route('farmer.pwa.password.reset', ['email' => $user->email]))->post(route('farmer.pwa.password.verify'), [
            'email' => $user->email,
            'otp' => '999999',
        ]);

        $response->assertRedirect(route('farmer.pwa.password.reset', ['email' => $user->email]));
        $response->assertSessionHasErrors(['otp']);
    }

    public function test_farmer_forgot_password_page_is_publicly_available(): void
    {
        $this->get(route('farmer.pwa.password.request'))
            ->assertOk()
            ->assertSee('SEND OTP');
    }

    private function makeFarmerUser(string $email): User
    {
        $barangay = Barangay::query()->create(['name' => 'Test Barangay', 'code' => 'TB-' . str()->upper(str()->random(4))]);
        $memberType = MemberType::query()->create([
            'code' => 'NM',
            'name' => 'New Member',
            'is_new_member' => true,
            'is_senior' => false,
            'requires_membership_fee' => true,
            'mortuary_eligible' => true,
        ]);

        $farmer = Farmer::query()->create([
            'farmer_code' => 'F-' . str()->upper(str()->random(8)),
            'first_name' => 'Test',
            'last_name' => 'Farmer',
            'birth_date' => now()->subYears(30)->toDateString(),
            'email' => $email,
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::ACTIVE->value,
            'membership_status' => MembershipStatus::ACTIVE->value,
            'registered_at' => now(),
            'activated_at' => now(),
        ]);

        return User::query()->create([
            'name' => $farmer->full_name,
            'email' => $email,
            'password' => 'secret123',
            'status' => 'active',
            'farmer_id' => $farmer->id,
        ]);
    }
}
