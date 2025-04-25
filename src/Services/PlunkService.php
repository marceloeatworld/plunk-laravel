<?php

namespace MarceloEatWorld\PlunkLaravel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlunkService
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * Create a new Plunk Service instance.
     *
     * @param string $apiKey
     * @param string $baseUrl
     * @param string $endpoint
     * @return void
     */
    public function __construct(string $apiKey, string $baseUrl, string $endpoint = '/api/v1/send')
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->endpoint = $endpoint;
    }

    /**
     * Send an email via Plunk API.
     *
     * @param string|array $to
     * @param string $subject
     * @param string $body
     * @param array $options
     * @return array
     */
    public function sendEmail($to, string $subject, string $body, array $options = []): array
    {
        try {
            $payload = [
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
            ];
            
            if (isset($options['from'])) {
                $payload['from'] = $options['from'];
            }
            
            if (isset($options['name'])) {
                $payload['name'] = $options['name'];
            }
            
            if (isset($options['reply'])) {
                $payload['reply'] = $options['reply'];
            }
            
            if (isset($options['subscribed'])) {
                $payload['subscribed'] = $options['subscribed'];
            }
            
            if (isset($options['headers']) && is_array($options['headers'])) {
                $payload['headers'] = $options['headers'];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . $this->endpoint, $payload);
            
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Plunk API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'payload' => $payload,
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Plunk API Error: ' . $response->status(),
                    'message' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception when sending email via Plunk', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }
}