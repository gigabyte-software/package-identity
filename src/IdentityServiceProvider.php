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
        $this->configureSanctum();

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

    /**
     * Configure Sanctum to work correctly with the sliding inactivity window.
     *
     * - Disables Sanctum's absolute expiration (based on created_at) so tokens
     *   don't hard-expire regardless of activity.
     * - Disables Sanctum's automatic last_used_at tracking so the
     *   EnsureTokenIsActive middleware can read the genuine last activity
     *   timestamp before deciding to extend or expire the session.
     * - Registers an authentication callback that bypasses Sanctum's built-in
     *   created_at-based expiration, deferring lifetime management entirely
     *   to EnsureTokenIsActive.
     */
    protected function configureSanctum(): void
    {
        config([
            'sanctum.expiration' => null,
            'sanctum.last_used_at' => false,
        ]);

        Sanctum::authenticateAccessTokensUsing(function ($accessToken, $isValid) {
            return (bool) $accessToken;
        });
    }
}
