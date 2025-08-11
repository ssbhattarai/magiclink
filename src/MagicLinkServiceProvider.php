<?php

namespace Ssbhattarai\MagicLink;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Ssbhattarai\MagicLink\Console\Commands\CheckMagicLinkCommand;
use Ssbhattarai\MagicLink\Console\Commands\CleanupExpiredTokensCommand;
use Ssbhattarai\MagicLink\Services\MagicLinkService;

class MagicLinkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/magiclink.php', 'magiclink');

        $this->app->singleton(MagicLinkService::class, function ($app) {
            return new MagicLinkService;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/magiclink.php' => config_path('magiclink.php'),
            ], 'magiclink-config');

            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/magiclink'),
            ], 'magiclink-views');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'magiclink-migrations');

            // Register commands
            $this->commands([
                CheckMagicLinkCommand::class,
                CleanupExpiredTokensCommand::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'magiclink');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('magiclink.prefix'),
            'middleware' => config('magiclink.middleware'),
        ];
    }
}
