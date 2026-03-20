<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->useStoragePath(base_path('tests/storage'));

        $appStorage = storage_path('app');
        if (! is_dir($appStorage)) {
            mkdir($appStorage, 0777, true);
        }
    }

    public function test_install_test_route_returns_lockout_message_after_too_many_attempts(): void
    {
        $ip = '127.0.0.1';
        $rateLimitKey = 'install-test|'.sha1($ip);

        RateLimiter::clear($rateLimitKey);
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($rateLimitKey, 600);
        }

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('install.test'), []);

        $response
            ->assertSessionHasErrors(['db_connection'])
            ->assertSessionHas('install_test_lockout_seconds')
            ->assertRedirect();

        $this->assertGreaterThan(0, (int) session('install_test_lockout_seconds'));

        RateLimiter::clear($rateLimitKey);
    }

    public function test_install_test_route_uses_named_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('install.test');

        $this->assertNotNull($route);
        $this->assertContains('throttle:install-test', $route->middleware());
    }

    public function test_install_test_redirects_to_login_when_app_is_already_installed(): void
    {
        file_put_contents(storage_path('app/install.lock'), '{"installed_at":"2026-03-19 00:00:00"}');

        $response = $this->post(route('install.test'), []);

        $response->assertRedirect(route('login'));

        @unlink(storage_path('app/install.lock'));
    }

    public function test_installer_log_channel_is_configured(): void
    {
        $channel = config('logging.channels.installer');
        $path = str_replace('\\', '/', (string) ($channel['path'] ?? ''));

        $this->assertIsArray($channel);
        $this->assertSame('daily', $channel['driver'] ?? null);
        $this->assertStringEndsWith('storage/logs/installer.log', $path);
        $this->assertSame('info', $channel['level'] ?? null);
    }
}
