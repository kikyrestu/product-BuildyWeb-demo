<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Before installation completes, avoid database-backed cache for limiter checks.
        if (! is_file(storage_path('app/install.lock')) && config('cache.default') === 'database') {
            config([
                'cache.default' => 'file',
                'cache.limiter' => 'file',
            ]);
        }

        RateLimiter::for('install-test', function (Request $request): Limit {
            return Limit::perMinute(20)->by((string) $request->ip());
        });
    }
}
