<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            // Ignore stale public/hot on production to force manifest assets.
            Vite::useHotFile(storage_path('framework/vite.hot'));

            // Shared hosting often serves apps from a subdirectory path.
            if (! app()->runningInConsole() && ! config('app.asset_url')) {
                $basePath = request()->getBasePath();

                if ($basePath !== '' && $basePath !== '/') {
                    config([
                        'app.asset_url' => rtrim(request()->getSchemeAndHttpHost().$basePath, '/'),
                    ]);
                }
            }
        }

        // Before installation completes, avoid database-backed stateful services.
        if (! is_file(storage_path('app/install.lock'))) {
            if (config('cache.default') === 'database') {
                config([
                    'cache.default' => 'file',
                    'cache.limiter' => 'file',
                ]);
            }

            if (config('session.driver') === 'database') {
                config(['session.driver' => 'file']);
            }
        }

        RateLimiter::for('install-test', function (Request $request): Limit {
            return Limit::perMinute(20)->by((string) $request->ip());
        });
    }
}
