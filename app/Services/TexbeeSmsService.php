<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TexbeeSmsService
{
    public function send(string $to, string $message, ?string $sender = null): array
    {
        $baseUrl = trim((string) config('services.texbee.base_url', env('TEXBEE_BASE_URL', 'https://api.textbee.dev/api/v1')));
        $apiKey = trim((string) config('services.texbee.key', env('TEXBEE_API_KEY')));
        $deviceId = trim((string) config('services.texbee.device_id', env('TEXBEE_DEVICE_ID')));

        if ($baseUrl === '' || $apiKey === '' || $deviceId === '' || trim($to) === '' || trim($message) === '') {
            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'error' => 'Missing Texbee SMS configuration or message data.',
                'payload' => null,
            ];
        }

        $payload = [
            'recipients' => [$to],
            'message' => $message,
        ];

        $authHeader = trim((string) config('services.texbee.auth_header', env('TEXBEE_AUTH_HEADER', 'x-api-key')));
        $sendUrl = rtrim($baseUrl, '/') . '/gateway/devices/' . $deviceId . '/send-sms';

        $response = Http::timeout(30)
            ->withHeaders([
                $authHeader => $apiKey,
                'Accept' => 'application/json',
            ])
            ->asJson()
            ->post($sendUrl, $payload);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
            'payload' => $payload,
            'error' => $response->successful() ? null : 'Texbee SMS request failed.',
        ];
    }
}