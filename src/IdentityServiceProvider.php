<?php

namespace Gigabyte\Identity;

use Gigabyte\Identity\Http\Middleware\ForceJsonResponse;
use Gigabyte\Identity\Models\PersonalAccessToken;
use Gigabyte\Identity\Services\IdentityService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/identity.php', 'identity');

        $this->app->singleton(IdentityService::class);
    }

    public function boot(): void
    {
        if (config('identity.override_sanctum_model', true)) {
            Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        }

        if (config('identity.run_sanctum_migration', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations/sanctum');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/core');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'identity');

        Route::middleware(['api', ForceJsonResponse::class])
            ->prefix('api')
            ->group(__DIR__.'/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/identity.php' => config_path('identity.php'),
            ], 'identity-config');

            $this->publishes([
                __DIR__.'/../database/migrations/core' => database_path('migrations'),
            ], 'identity-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/identity'),
            ], 'identity-views');
        }
    }
}
