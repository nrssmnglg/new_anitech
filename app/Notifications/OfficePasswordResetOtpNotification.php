<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfficePasswordResetOtpNotification extends Notification implements ShouldQueue
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
            ->subject('Your AniTech office password reset OTP')
            ->view('emails.password-reset-otp', [
                'subject' => 'Your AniTech office password reset OTP',
                'greeting' => 'Hello AniTech Staff,',
                'intro' => 'We received a request to reset your AniTech office account password.',
                'code' => $this->code,
                'actionLabel' => 'Open Reset Page',
                'actionUrl' => route('password.reset', ['email' => $notifiable->email]),
                'logoUrl' => asset('figures/anitech-logo-official.svg'),
            ]);
    }
}
