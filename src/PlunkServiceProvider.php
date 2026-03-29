<?php

namespace MarceloEatWorld\PlunkLaravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use MarceloEatWorld\PlunkLaravel\Services\PlunkService;
use MarceloEatWorld\PlunkLaravel\Transport\PlunkTransport;

class PlunkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/plunk.php' => config_path('plunk.php'),
        ], 'plunk-config');

        Mail::extend('plunk', function () {
            return new PlunkTransport($this->app->make(PlunkService::class));
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/plunk.php',
            'plunk',
        );

        $this->app->singleton(PlunkService::class, function () {
            return new PlunkService(
                config('plunk.api_key', ''),
                config('plunk.api_url', 'https://api.useplunk.com'),
                config('plunk.endpoint', '/v1/send'),
            );
        });
    }
}
