<?php

namespace Tests\Feature\Admin;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_filters_notifications_by_module_and_state_and_returns_latest_unread_notification(): void
    {
        $admin = $this->makeAdminUser();

        $this->insertNotification(
            $admin->id,
            NotificationType::RENEWAL_REQUEST_SUBMITTED,
            'Renewal waiting',
            'Renewal request submitted.',
            ['renewal_request_id' => 41, 'source' => 'mobile'],
            null,
            now()->subMinute(),
        );

        $this->insertNotification(
            $admin->id,
            NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED,
            'New membership application',
            'Juan Dela Cruz submitted a new membership application APP-2026-ABC123 and uploaded Cedula.',
            ['application_id' => 55, 'source' => 'mobile'],
            null,
            now(),
        );

        $this->insertNotification(
            $admin->id,
            NotificationType::QUERY_RECEIVED,
            'Query answered',
            'A query was received.',
            ['query_id' => 77],
            now()->subMinutes(5),
            now()->subMinutes(5),
        );

        $response = $this->actingAs($admin)->getJson(route('admin.notifications.feed', [
            'module' => 'applications',
            'state' => 'unread',
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.unread', 1)
            ->assertJsonPath('summary.read', 0)
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('latest_notification.subject', 'New membership application')
            ->assertJsonPath('latest_notification.module_label', 'Applications')
            ->assertJsonPath('latest_notification.source_label', 'MOBILE');

        $html = (string) $response->json('notifications_html');

        $this->assertStringContainsString('New Membership Application', $html);
        $this->assertStringContainsString('aria-label="Open record"', $html);
        $this->assertStringNotContainsString('Renewal waiting', $html);
        $this->assertStringNotContainsString('Query answered', $html);
        $this->assertNotEmpty($response->json('latest_notification.signature'));
    }

    private function makeAdminUser(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');

        $user = User::query()->create([
            'name' => 'Notification Admin',
            'email' => 'notification-admin-' . uniqid() . '@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $user->assignRole(User::ROLE_ADMIN);

        return $user;
    }

    private function insertNotification(
        int $userId,
        NotificationType $type,
        string $subject,
        string $message,
        array $payload = [],
        $readAt = null,
        $createdAt = null,
    ): void {
        $timestamp = ($createdAt ?? now())->toDateTimeString();

        $notificationId = DB::table('notifications')->insertGetId([
            'type' => $type->value,
            'channel' => 'database',
            'subject' => $subject,
            'message' => $message,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'queued',
            'queued_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('notification_recipients')->insert([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'status' => $readAt ? 'read' : 'pending',
            'read_at' => $readAt?->toDateTimeString(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
