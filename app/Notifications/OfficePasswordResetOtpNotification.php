<?php

namespace App\Notifications;

use App\Notifications\Channels\BrevoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfficePasswordResetOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {
    }

    public function via(object $notifiable): array
    {
        return [BrevoChannel::class];
    }

    public function toBrevo(object $notifiable): array
    {
        $subject = 'Your AniTech office password reset OTP';

        return [
            'sender' => [
                'name' => (string) config('services.brevo.sender_name', config('app.name', 'AniTech')),
                'email' => (string) config('services.brevo.sender_email'),
            ],
            'to' => [
                'email' => (string) $notifiable->email,
                'name' => (string) ($notifiable->name ?? 'AniTech Staff'),
            ],
            'subject' => $subject,
            'html' => view('emails.password-reset-otp', [
                'subject' => $subject,
                'greeting' => 'Hello AniTech Staff,',
                'intro' => 'We received a request to reset your AniTech office account password.',
                'code' => $this->code,
                'actionLabel' => 'Open Reset Page',
                'actionUrl' => route('password.reset', ['email' => $notifiable->email]),
                'logoUrl' => asset('figures/anitech-logo-official.svg'),
            ])->render(),
        ];
    }
}
