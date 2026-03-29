<?php

namespace MarceloEatWorld\PlunkLaravel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlunkService
{
    public function __construct(
        protected readonly string $apiKey,
        protected readonly string $baseUrl,
        protected readonly string $endpoint = '/v1/send',
    ) {}

    /**
     * Send a transactional email.
     *
     * @param string|array<string|array{name: string, email: string}> $to
     */
    public function sendEmail(string|array $to, string $subject, string $body, array $options = []): array
    {
        $payload = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ];

        foreach (['from', 'name', 'reply', 'subscribed', 'headers', 'attachments'] as $field) {
            if (isset($options[$field])) {
                $payload[$field] = $options[$field];
            }
        }

        return $this->request('POST', $this->endpoint, $payload);
    }

    /**
     * Send an email using a Plunk template.
     *
     * @param string|array<string|array{name: string, email: string}> $to
     * @param array<string, mixed> $data Template variables
     */
    public function sendTemplate(string|array $to, string $templateId, array $data = [], array $options = []): array
    {
        $payload = [
            'to' => $to,
            'template' => $templateId,
        ];

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        foreach (['from', 'name', 'reply', 'subscribed', 'headers'] as $field) {
            if (isset($options[$field])) {
                $payload[$field] = $options[$field];
            }
        }

        return $this->request('POST', $this->endpoint, $payload);
    }

    /**
     * Track an event for a contact (auto-creates contact if needed).
     *
     * @param array<string, mixed> $data Event data / contact metadata
     */
    public function trackEvent(string $email, string $event, array $data = [], bool $subscribed = true): array
    {
        $payload = [
            'email' => $email,
            'event' => $event,
            'subscribed' => $subscribed,
        ];

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        return $this->request('POST', '/v1/track', $payload);
    }

    /**
     * Verify an email address.
     */
    public function verifyEmail(string $email): array
    {
        return $this->request('POST', '/v1/verify', ['email' => $email]);
    }

    /**
     * Make an authenticated request to the Plunk API.
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->$method($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return $response->json() ?? ['success' => true];
            }

            Log::error('Plunk API error', [
                'status' => $response->status(),
                'response' => $response->body(),
                'endpoint' => $endpoint,
            ]);

            return [
                'success' => false,
                'error' => 'Plunk API error: HTTP ' . $response->status(),
                'message' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Plunk API exception', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
