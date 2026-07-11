<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FarmerResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your AniTech Farmer password reset OTP')
            ->view('emails.password-reset-otp', [
                'subject' => 'Your AniTech Farmer password reset OTP',
                'greeting' => 'Hello' . ($notifiable->name ? ' ' . $notifiable->name : ' Farmer') . ',',
                'intro' => 'We received a request to reset your AniTech Farmer account password.',
                'code' => $this->code,
                'actionLabel' => 'Open Reset Page',
                'actionUrl' => $this->resetUrl($notifiable),
                'logoUrl' => asset('figures/anitech-logo-official.svg'),
            ]);
    }

    private function resetUrl(object $notifiable): string
    {
        return route('farmer.pwa.password.reset', [
            'email' => $notifiable->email,
        ]);
    }
}
