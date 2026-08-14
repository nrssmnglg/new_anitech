<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toBrevo')) {
            return;
        }

        $message = $notification->toBrevo($notifiable);
        $apiKey = (string) config('services.brevo.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY is not configured.');
        }

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $apiKey,
            'content-type' => 'application/json',
        ])
            ->connectTimeout(5)
            ->timeout(10)
            ->retry(1, 500)
            ->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => $message['sender']['name'],
                'email' => $message['sender']['email'],
            ],
            'to' => [[
                'email' => $message['to']['email'],
                'name' => $message['to']['name'] ?? '',
            ]],
            'subject' => $message['subject'],
            'htmlContent' => $message['html'],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Brevo send failed with HTTP status ' . $response->status() . '.');
        }
    }
}
