# Laravel Plunk

#1 Laravel mail transport for [Plunk](https://useplunk.com) with event tracking and email verification, compatible with native PHP. Works with both the official Plunk service and self-hosted instances.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

## Installation

```bash
composer require marceloeatworld/plunk-laravel
```

Publish the config file:

```bash
php artisan vendor:publish --tag="plunk-config"
```

## Configuration

Add the Plunk mailer to `config/mail.php`:

```php
'mailers' => [
    // ... other mailers

    'plunk' => [
        'transport' => 'plunk',
    ],
],
```

Add to your `.env`:

```env
MAIL_MAILER=plunk
PLUNK_API_KEY=sk_your_secret_key
```

For self-hosted Plunk:

```env
PLUNK_API_URL=https://plunk.yourdomain.com
PLUNK_API_ENDPOINT=/api/v1/send
```

## Usage

### Sending Emails (Laravel Mail)

Use Laravel's mail system as usual -- emails are sent through Plunk automatically:

```php
Mail::to('user@example.com')->send(new WelcomeMail());
```

Attachments, CC, BCC, and reply-to are all supported:

```php
Mail::to('user@example.com')
    ->cc('manager@example.com')
    ->bcc('audit@example.com')
    ->send(new InvoiceMail());
```

### Direct API Access (Facade)

Use the `Plunk` facade for direct API access:

```php
use MarceloEatWorld\PlunkLaravel\Facades\Plunk;

// Send email directly
Plunk::sendEmail('user@example.com', 'Welcome', '<h1>Hello!</h1>', [
    'from' => 'hello@yourdomain.com',
    'name' => 'Your App',
    'reply' => 'support@yourdomain.com',
]);

// Send with a Plunk template
Plunk::sendTemplate('user@example.com', 'tmpl_welcome', [
    'name' => 'John',
    'action_url' => 'https://app.example.com/verify',
]);
```

### Event Tracking

Track events to trigger automations and segment contacts:

```php
Plunk::trackEvent('user@example.com', 'signed_up', [
    'plan' => 'pro',
    'source' => 'landing_page',
]);
```

### Email Verification

Verify email addresses before sending:

```php
$result = Plunk::verifyEmail('user@example.com');

if ($result['data']['valid']) {
    // Email is valid
}
```

### Multiple Recipients with Names

```php
Plunk::sendEmail(
    [
        'alice@example.com',
        ['name' => 'Bob Smith', 'email' => 'bob@example.com'],
    ],
    'Team Update',
    '<p>Hello team!</p>',
);
```

### Attachments (Direct API)

```php
Plunk::sendEmail('user@example.com', 'Your Invoice', '<p>Attached.</p>', [
    'from' => 'billing@yourdomain.com',
    'attachments' => [
        [
            'filename' => 'invoice.pdf',
            'content' => base64_encode(file_get_contents('/path/to/invoice.pdf')),
            'contentType' => 'application/pdf',
        ],
    ],
]);
```

## Testing

```bash
composer test
```

## Credits

- [Marcelo EatWorld](https://github.com/MarceloEatWorld)

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
