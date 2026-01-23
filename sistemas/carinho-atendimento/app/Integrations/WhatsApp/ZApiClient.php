<?php

namespace App\Integrations\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ZApiClient
{
    public function sendTextMessage(string $phone, string $message): array
    {
        return $this->request('send-text', [
            'phone' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
    }

    public function sendMediaMessage(string $phone, string $mediaUrl, ?string $caption = null): array
    {
        return $this->request('send-image', [
            'phone' => $this->normalizePhone($phone),
            'image' => $mediaUrl,
            'caption' => $caption,
        ]);
    }

    public function normalizeInbound(array $payload): array
    {
        $rawPhone = data_get($payload, 'phone')
            ?? data_get($payload, 'from')
            ?? data_get($payload, 'sender')
            ?? data_get($payload, 'message.from');

        $body = data_get($payload, 'message')
            ?? data_get($payload, 'text')
            ?? data_get($payload, 'body')
            ?? data_get($payload, 'message.text')
            ?? '';

        $mediaUrl = data_get($payload, 'media.url')
            ?? data_get($payload, 'message.mediaUrl')
            ?? data_get($payload, 'message.media')
            ?? null;

        return [
            'provider' => 'z-api',
            'event' => data_get($payload, 'event', 'message'),
            'phone' => $this->normalizePhone((string) $rawPhone),
            'name' => (string) (data_get($payload, 'senderName') ?? data_get($payload, 'sender.name') ?? ''),
            'body' => is_string($body) ? $body : json_encode($body),
            'media_url' => $mediaUrl,
            'received_at' => data_get($payload, 'timestamp')
                ? \Carbon\Carbon::createFromTimestamp((int) data_get($payload, 'timestamp'))
                : now(),
            'raw' => $payload,
        ];
    }

    public function isSignatureValid(string $payload, ?string $signature): bool
    {
        $secret = config('integrations.whatsapp.webhook_secret');

        if (!$secret) {
            return true;
        }

        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    private function request(string $path, array $payload): array
    {
        $response = Http::withHeaders($this->headers())
            ->connectTimeout((int) config('integrations.whatsapp.connect_timeout', 3))
            ->timeout((int) config('integrations.whatsapp.timeout', 10))
            ->post($this->endpoint($path), $payload);

        return [
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body' => $response->json(),
        ];
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('integrations.whatsapp.base_url'), '/');
        $instanceId = trim((string) config('integrations.whatsapp.instance_id'), '/');
        $token = trim((string) config('integrations.whatsapp.token'), '/');

        return "{$baseUrl}/instances/{$instanceId}/token/{$token}/{$path}";
    }

    private function headers(): array
    {
        $clientToken = config('integrations.whatsapp.client_token');

        if ($clientToken) {
            return ['client-token' => $clientToken];
        }

        return [];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\\D+/', '', $phone ?? '');

        return $digits ? $digits : Str::of($phone)->trim()->toString();
    }
}
