<?php

namespace MarceloEatWorld\PlunkLaravel;

use Illuminate\Support\ServiceProvider;
use MarceloEatWorld\PlunkLaravel\Services\PlunkService;
use MarceloEatWorld\PlunkLaravel\Transport\PlunkTransport;
use Illuminate\Support\Facades\Mail;

class PlunkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/plunk.php' => config_path('plunk.php'),
        ], 'plunk-config');

        Mail::extend('plunk', function () {
            return new PlunkTransport(app(PlunkService::class));
        });
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/plunk.php', 'plunk'
        );

        $this->app->singleton(PlunkService::class, function ($app) {
            return new PlunkService(
                config('plunk.api_key'),
                config('plunk.api_url'),
                config('plunk.endpoint', '/api/v1/send')
            );
        });
    }
}