# Laravel Plunk Mail Transport

A Laravel mail transport for sending emails via the Plunk API. Works with both the official Plunk service and self-hosted Plunk instances.

## Installation

You can install the package via Composer:

```bash
composer require MarceloEatWorld/plunk-laravel
```

Publish the config file:

```bash
php artisan vendor:publish --tag="plunk-config"
```

## Configuration

Add these variables to your `.env` file:

```env
MAIL_MAILER=plunk
PLUNK_API_KEY=your-plunk-api-key
PLUNK_API_URL=https://api.useplunk.com
```

For self-hosted Plunk installations, use:

```env
PLUNK_API_URL=https://your-domain.com
PLUNK_API_ENDPOINT=/api/v1/send
```

## Usage

Once configured, you can use the Laravel mailer as usual:

```php
Mail::to('example@example.com')
    ->send(new App\Mail\WelcomeMail());
```

## Credits

- [Marcelo EatWorld](https://github.com/MarceloEatWorld)
- All Contributors

## License

The MIT License (MIT). Please see the License File for more information.
