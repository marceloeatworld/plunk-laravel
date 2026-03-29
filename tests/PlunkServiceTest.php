<?php

namespace MarceloEatWorld\PlunkLaravel\Tests;

use Illuminate\Support\Facades\Http;
use MarceloEatWorld\PlunkLaravel\Services\PlunkService;
use Orchestra\Testbench\TestCase;

class PlunkServiceTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\MarceloEatWorld\PlunkLaravel\PlunkServiceProvider::class];
    }

    public function test_sends_basic_email(): void
    {
        Http::fake([
            'https://api.useplunk.com/v1/send' => Http::response([
                'success' => true,
                'data' => [
                    'emails' => [['contact' => ['id' => '1', 'email' => 'test@example.com']]],
                    'timestamp' => '2024-01-01T00:00:00Z',
                ],
            ]),
        ]);

        $service = new PlunkService('sk_test_key', 'https://api.useplunk.com', '/v1/send');
        $result = $service->sendEmail('test@example.com', 'Test Subject', '<p>Hello</p>');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.useplunk.com/v1/send'
                && $request['to'] === 'test@example.com'
                && $request['subject'] === 'Test Subject'
                && $request['body'] === '<p>Hello</p>'
                && $request->hasHeader('Authorization', 'Bearer sk_test_key');
        });
    }

    public function test_sends_email_with_all_options(): void
    {
        Http::fake([
            'https://api.useplunk.com/v1/send' => Http::response(['success' => true]),
        ]);

        $service = new PlunkService('sk_test_key', 'https://api.useplunk.com', '/v1/send');
        $service->sendEmail('test@example.com', 'Test', '<p>Hi</p>', [
            'from' => 'sender@example.com',
            'name' => 'Sender Name',
            'reply' => 'reply@example.com',
            'subscribed' => true,
            'headers' => ['X-Custom' => 'value'],
            'attachments' => [
                ['filename' => 'doc.pdf', 'content' => base64_encode('pdf-content'), 'contentType' => 'application/pdf'],
            ],
        ]);

        Http::assertSent(function ($request) {
            return $request['from'] === 'sender@example.com'
                && $request['name'] === 'Sender Name'
                && $request['reply'] === 'reply@example.com'
                && $request['subscribed'] === true
                && $request['headers'] === ['X-Custom' => 'value']
                && count($request['attachments']) === 1
                && $request['attachments'][0]['filename'] === 'doc.pdf';
        });
    }

    public function test_sends_template_email(): void
    {
        Http::fake([
            'https://api.useplunk.com/v1/send' => Http::response(['success' => true]),
        ]);

        $service = new PlunkService('sk_test_key', 'https://api.useplunk.com', '/v1/send');
        $result = $service->sendTemplate('test@example.com', 'tmpl_123', [
            'name' => 'John',
            'action_url' => 'https://example.com/verify',
        ]);

        Http::assertSent(function ($request) {
            return $request['to'] === 'test@example.com'
                && $request['template'] === 'tmpl_123'
                && $request['data']['name'] === 'John'
                && !isset($request['subject'])
                && !isset($request['body']);
        });
    }

    public function test_tracks_event(): void
    {
        Http::fake([
            'https://api.useplunk.com/v1/track' => Http::response([
                'success' => true,
                'data' => ['contact' => 'c_1', 'event' => 'e_1', 'timestamp' => '2024-01-01T00:00:00Z'],
            ]),
        ]);

        $service = new PlunkService('sk_test_key', 'https://api.useplunk.com');
        $result = $service->trackEvent('user@example.com', 'signed_up', ['plan' => 'pro']);

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v1/track')
                && $request['email'] === 'user@example.com'
                && $request['event'] === 'signed_up'
                && $request['data'] === ['plan' => 'pro']
                && $request['subscribed'] === true;
        });
    }

    public function test_verifies_email(): void
    {
        Http::fake([
            'https://api.useplunk.com/v1/verify' => Http::response([
                'success' => true,
                'data' => ['email' => 'test@example.com', 'valid' => true, 'isDisposable' => false],
            ]),
        ]);

        $service = new PlunkService('sk_test_key', 'https://api.useplunk.com');
        $result = $service->verifyEmail('test@example.com');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['data']['valid']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v1/verify')
                && $request['email'] === 'test@example.com';
        });
    }

    public function test_handles_api_error(): void
    {
        Http::fake([
            'https://api.useplunk.com/v1/send' => Http::response(
                ['success' => false, 'error' => ['code' => 'UNAUTHORIZED']],
                401,
            ),
        ]);

        $service = new PlunkService('sk_bad_key', 'https://api.useplunk.com', '/v1/send');
        $result = $service->sendEmail('test@example.com', 'Test', '<p>Hi</p>');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('401', $result['error']);
    }

    public function test_handles_exception(): void
    {
        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $service = new PlunkService('sk_test_key', 'https://api.useplunk.com', '/v1/send');
        $result = $service->sendEmail('test@example.com', 'Test', '<p>Hi</p>');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection timeout', $result['error']);
    }

    public function test_sends_to_multiple_recipients(): void
    {
        Http::fake([
            'https://api.useplunk.com/v1/send' => Http::response(['success' => true]),
        ]);

        $service = new PlunkService('sk_test_key', 'https://api.useplunk.com', '/v1/send');
        $service->sendEmail(
            ['alice@example.com', ['name' => 'Bob', 'email' => 'bob@example.com']],
            'Test',
            '<p>Hi</p>',
        );

        Http::assertSent(function ($request) {
            $to = $request['to'];
            return is_array($to)
                && $to[0] === 'alice@example.com'
                && $to[1]['name'] === 'Bob'
                && $to[1]['email'] === 'bob@example.com';
        });
    }

    public function test_uses_custom_endpoint_for_self_hosted(): void
    {
        Http::fake([
            'https://plunk.myapp.com/api/v1/send' => Http::response(['success' => true]),
        ]);

        $service = new PlunkService('sk_test_key', 'https://plunk.myapp.com', '/api/v1/send');
        $service->sendEmail('test@example.com', 'Test', '<p>Hi</p>');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://plunk.myapp.com/api/v1/send';
        });
    }
}
