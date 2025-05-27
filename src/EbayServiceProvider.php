<?php

namespace Andyts93\LaravelEbay;

use Andyts93\LaravelEbay\Console\Commands\EbaySetupCommand;
use Andyts93\LaravelEbay\Services\EbayRestApiService;
use Illuminate\Support\ServiceProvider;

class EbayServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/ebay.php' => config_path('ebay.php'),
        ], 'ebay-config');

        $this->publishesMigrations([
            __DIR__ . '/database/migrations' => database_path('migrations')
        ], 'ebay-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                EbaySetupCommand::class,
            ]);
        }

        $this->mergeConfigFrom(__DIR__ . '/config/ebay.php', 'ebay');
    }

    public function register(): void
    {
        $this->app->singleton(EbayRestApiService::class, function ($app) {
            return new EbayRestApiService();
        });

        $this->app->alias(EbayRestApiService::class, 'ebay-api');
    }

    public function provides(): array
    {
        return [
            EbayRestApiService::class,
            'ebay-api'
        ];
    }
}
