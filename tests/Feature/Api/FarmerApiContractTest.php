<?php

namespace Tests\Feature\Api;

use App\Enums\MembershipStatus;
use App\Models\Advisory;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerProfile;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\Payment;
use App\Models\PaymentAssessment;
use App\Models\PaymentMethod;
use App\Models\Query;
use App\Models\QueryCategory;
use App\Models\RenewalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FarmerApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_dashboard_profile_membership_and_notifications_contracts_return_expected_payloads(): void
    {
        [$farmer, $user, $barangay, $association, $memberType] = $this->createFarmerMemberWithRelations();

        Advisory::query()->create([
            'title' => 'Weather Advisory',
            'slug' => 'weather-advisory',
            'content' => 'Expect heavy rain.',
            'status' => 'Published',
            'audience_type' => 'all',
            'published_at' => now(),
        ]);

        $this->createNotificationRecipient($farmer, $user, 'renewal_reminder', [
            'target_year' => now()->year,
        ]);

        $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/dashboard')
            ->assertOk()
            ->assertJsonPath('data.account.email', $user->email)
            ->assertJsonPath('data.profile.full_name', 'Ana Dela Cruz')
            ->assertJsonPath('data.stats.membership_status', 'active');

        $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/me')
            ->assertOk()
            ->assertJsonPath('data.account.email', $user->email);

        $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/profile')
            ->assertOk()
            ->assertJsonPath('data.profile.mobile_number', '09170000001')
            ->assertJsonPath('data.barangay.name', $barangay->name);

        $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/membership')
            ->assertOk()
            ->assertJsonPath('data.member_type.code', $memberType->code)
            ->assertJsonPath('data.membership_status', 'active');

        $notificationResponse = $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/notifications');

        $notificationResponse
            ->assertOk()
            ->assertJsonPath('meta.summary.total', 1)
            ->assertJsonPath('meta.summary.unread', 1);

        $recipientId = $notificationResponse->json('data.0.recipient_id');

        $this->actingAs($user, 'farmer_pwa')
            ->postJson('/api/farmer/notifications/' . $recipientId . '/read')
            ->assertOk()
            ->assertJsonPath('data.updated', true)
            ->assertJsonPath('meta.summary.unread', 0);
    }

    public function test_farmer_renewals_and_payments_contracts_return_expected_payloads(): void
    {
        [$farmer, $user] = $this->createFarmerMember();

        $renewal = RenewalRequest::query()->create([
            'farmer_id' => $farmer->id,
            'transaction_type' => 'Renewal',
            'status' => 'Approved',
            'application_no' => 'REN-2026-0001',
            'year' => now()->year,
            'source' => 'mobile',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
            'is_late' => false,
        ]);

        $assessment = PaymentAssessment::query()->create([
            'membership_transaction_id' => $renewal->id,
            'fee_schedule_id' => FeeSchedule::query()->create([
                'member_type_id' => $farmer->member_type_id,
                'year' => now()->year,
                'membership_fee' => 100,
                'annual_due' => 100,
                'mortuary_fee' => 150,
                'is_active' => true,
            ])->id,
            'total_amount_due' => 350,
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'status' => 'Paid',
        ]);

        $method = PaymentMethod::query()->create([
            'code' => 'GCASH',
            'name' => 'GCash',
            'is_online' => true,
            'status' => 'Active',
        ]);

        Payment::query()->create([
            'payment_assessment_id' => $assessment->id,
            'payment_method_id' => $method->id,
            'amount_paid' => 350,
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'reference_no' => 'PAY-0001',
            'paid_at' => now(),
            'verified_at' => now(),
            'status' => 'Verified',
        ]);

        $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/renewals')
            ->assertOk()
            ->assertJsonPath('data.0.application_no', 'REN-2026-0001')
            ->assertJsonPath('data.0.assessment.total_amount_due', 350);

        $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/payments')
            ->assertOk()
            ->assertJsonPath('data.0.reference_no', 'PAY-0001')
            ->assertJsonPath('data.0.payment_method.code', 'GCASH')
            ->assertJsonPath('data.0.amount_paid', 350);
    }

    public function test_farmer_can_create_and_reply_to_inquiry_and_cannot_view_other_farmer_inquiry(): void
    {
        [$farmer, $user] = $this->createFarmerMember();
        [$otherFarmer, $otherUser] = $this->createFarmerMember('other@example.test', '09170000002', 'Pedro', 'Santos');

        QueryCategory::query()->create([
            'code' => 'GEN',
            'name' => 'General',
            'description' => 'General concerns',
            'status' => 'Active',
        ]);

        $createResponse = $this->actingAs($user, 'farmer_pwa')
            ->postJson('/api/farmer/inquiries', [
                'subject' => 'Need help with renewal',
                'message' => 'Please assist with my renewal record.',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.subject', 'Need help with renewal')
            ->assertJsonPath('data.status', 'Open');

        $inquiryId = $createResponse->json('data.id');

        $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/inquiries')
            ->assertOk()
            ->assertJsonPath('meta.summary.total', 1)
            ->assertJsonPath('data.0.subject', 'Need help with renewal');

        $replyResponse = $this->actingAs($user, 'farmer_pwa')
            ->postJson('/api/farmer/inquiries/' . $inquiryId . '/reply', [
                'message' => 'Following up on my concern.',
            ]);

        $replyResponse
            ->assertOk()
            ->assertJsonPath('data.status', 'Answered')
            ->assertJsonCount(1, 'data.responses');

        $foreignQuery = Query::query()->create([
            'farmer_id' => $otherFarmer->id,
            'category_id' => QueryCategory::query()->value('id'),
            'subject' => 'Other inquiry',
            'message' => 'Private message',
            'status' => 'Open',
            'created_at' => now(),
        ]);

        $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/inquiries/' . $foreignQuery->id)
            ->assertForbidden();
    }

    public function test_farmer_advisories_only_return_targeted_records(): void
    {
        [$farmer, $user, $barangay, $association, $memberType] = $this->createFarmerMemberWithRelations();

        Advisory::query()->create([
            'title' => 'Global Advisory',
            'slug' => 'global-advisory',
            'content' => 'Visible to all.',
            'status' => 'Published',
            'audience_type' => 'all',
            'published_at' => now(),
        ]);

        Advisory::query()->create([
            'title' => 'Barangay Advisory',
            'slug' => 'barangay-advisory',
            'content' => 'Visible to barangay.',
            'status' => 'Published',
            'audience_type' => 'barangay',
            'barangay_id' => $barangay->id,
            'published_at' => now(),
        ]);

        Advisory::query()->create([
            'title' => 'Member Group Advisory',
            'slug' => 'member-group-advisory',
            'content' => 'Visible to member type.',
            'status' => 'Published',
            'audience_type' => 'group',
            'member_type_id' => $memberType->id,
            'published_at' => now(),
        ]);

        Advisory::query()->create([
            'title' => 'Hidden Advisory',
            'slug' => 'hidden-advisory',
            'content' => 'Not for this farmer.',
            'status' => 'Published',
            'audience_type' => 'barangay',
            'barangay_id' => Barangay::query()->create([
                'name' => 'Elsewhere',
                'code' => 'BRGY-ELSE',
                'status' => 'Active',
            ])->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user, 'farmer_pwa')
            ->getJson('/api/farmer/advisories');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    private function createFarmerMember(
        string $email = 'farmer@example.test',
        string $mobileNumber = '09170000001',
        string $firstName = 'Ana',
        string $lastName = 'Dela Cruz',
    ): array {
        [$farmer, $user] = $this->createFarmerMemberWithRelations($email, $mobileNumber, $firstName, $lastName);

        return [$farmer, $user];
    }

    private function createFarmerMemberWithRelations(
        string $email = 'farmer@example.test',
        string $mobileNumber = '09170000001',
        string $firstName = 'Ana',
        string $lastName = 'Dela Cruz',
    ): array {
        $barangay = Barangay::query()->create([
            'name' => 'San Roque ' . strtoupper(substr(md5($email), 0, 4)),
            'code' => 'BRGY-' . strtoupper(substr(md5($email), 0, 6)),
            'status' => 'Active',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'code' => 'ASC-' . strtoupper(substr(md5($email . '-assoc'), 0, 6)),
            'name' => 'Association ' . strtoupper(substr(md5($email), 0, 4)),
            'status' => 'Active',
        ]);

        $memberType = MemberType::query()->create([
            'code' => 'REG-' . strtoupper(substr(md5($email . '-member'), 0, 4)),
            'name' => 'Regular Member ' . strtoupper(substr(md5($email), 0, 4)),
            'requires_membership_fee' => true,
            'mortuary_eligible' => true,
            'status' => 'Active',
        ]);

        $farmer = Farmer::query()->create([
            'farmer_code' => 'FRM-' . strtoupper(substr(md5($email . '-farmer'), 0, 8)),
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'membership_status' => MembershipStatus::ACTIVE,
            'record_origin' => 'mobile',
            'registered_at' => now()->subYear(),
            'activated_at' => now()->subMonths(11),
        ]);

        FarmerProfile::query()->create([
            'farmer_id' => $farmer->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'sex' => 'Male',
            'civil_status' => 'Single',
            'address' => 'Purok 1',
            'mobile_number' => $mobileNumber,
            'birth_date' => '1990-01-01',
        ]);

        $user = User::factory()->create([
            'name' => $firstName . ' ' . $lastName,
            'email' => $email,
            'farmer_id' => $farmer->id,
            'status' => User::STATUS_ACTIVE,
            'role' => User::ROLE_FARMER,
        ]);

        return [$farmer, $user, $barangay, $association, $memberType];
    }

    private function createNotificationRecipient(Farmer $farmer, User $user, string $type, array $payload = []): int
    {
        $notificationId = DB::table('notifications')->insertGetId([
            'type' => $type,
            'channel' => 'database',
            'subject' => 'Notification',
            'message' => 'Notification message.',
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'queued',
            'queued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('notification_recipients')->insertGetId([
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'farmer_id' => $farmer->id,
            'recipient_address' => $user->email,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
