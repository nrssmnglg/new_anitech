<?php

namespace App\Notifications;

use App\Notifications\Channels\BrevoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FarmerResetPasswordNotification extends Notification
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
        $subject = 'Your AniTech Farmer password reset OTP';

        return [
            'sender' => [
                'name' => (string) config('services.brevo.sender_name', config('app.name', 'AniTech')),
                'email' => (string) config('services.brevo.sender_email'),
            ],
            'to' => [
                'email' => (string) $notifiable->email,
                'name' => (string) ($notifiable->name ?: 'Farmer'),
            ],
            'subject' => $subject,
            'html' => view('emails.password-reset-otp', [
                'subject' => $subject,
                'greeting' => 'Hello' . ($notifiable->name ? ' ' . $notifiable->name : ' Farmer') . ',',
                'intro' => 'We received a request to reset your AniTech Farmer account password.',
                'code' => $this->code,
                'actionLabel' => 'Open Reset Page',
                'actionUrl' => $this->resetUrl($notifiable),
                'logoUrl' => asset('figures/anitech-logo-official.svg'),
            ])->render(),
        ];
    }

    private function resetUrl(object $notifiable): string
    {
        return route('farmer.pwa.password.reset', [
            'email' => $notifiable->email,
        ]);
    }
}
