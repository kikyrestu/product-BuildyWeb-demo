<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class InstallerController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        return view('install.index');
    }

    public function install(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:120'],
            'db_connection' => ['required', 'in:pgsql,mysql'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_email' => ['required', 'string', 'email', 'max:255'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->testConnection(
                $validated['db_connection'],
                $validated['db_host'],
                (int) $validated['db_port'],
                $validated['db_database'],
                $validated['db_username'],
                $validated['db_password'] ?? ''
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['db_connection' => $this->connectionFailedMessage()])
                ->withInput();
        }

        $appName = trim((string) ($validated['app_name'] ?? ''));
        if ($appName === '') {
            $appName = 'SI Laundry';
        }

        try {
            $this->updateEnvFile([
                'APP_NAME' => '"'.$appName.'"',
                'DB_CONNECTION' => $validated['db_connection'],
                'DB_HOST' => $validated['db_host'],
                'DB_PORT' => (string) $validated['db_port'],
                'DB_DATABASE' => $validated['db_database'],
                'DB_USERNAME' => $validated['db_username'],
                'DB_PASSWORD' => (string) ($validated['db_password'] ?? ''),
            ]);

            config([
                'app.name' => $appName,
                'database.default' => $validated['db_connection'],
                'database.connections.'.$validated['db_connection'].'.host' => $validated['db_host'],
                'database.connections.'.$validated['db_connection'].'.port' => (string) $validated['db_port'],
                'database.connections.'.$validated['db_connection'].'.database' => $validated['db_database'],
                'database.connections.'.$validated['db_connection'].'.username' => $validated['db_username'],
                'database.connections.'.$validated['db_connection'].'.password' => (string) ($validated['db_password'] ?? ''),
            ]);

            DB::purge($validated['db_connection']);
            DB::reconnect($validated['db_connection']);

            Artisan::call('migrate', ['--force' => true]);

            $ownerEmail = strtolower($validated['owner_email']);
            $existingOwner = User::query()->where('email', $ownerEmail)->first();

            User::query()->updateOrCreate(
                ['email' => $ownerEmail],
                [
                    'name' => $validated['owner_name'],
                    'username' => $existingOwner?->username ?? $this->generateUniqueUsername($validated['owner_name'], $ownerEmail),
                    'password' => $validated['owner_password'],
                    'role' => 'owner',
                    'is_active' => true,
                ]
            );

            $this->writeInstallLock();
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['db_connection' => $this->setupFailedMessage()])
                ->withInput();
        }

        return redirect()->route('login')->with('status', 'Setup database berhasil. Silakan login.');
    }

    public function test(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        $attemptKey = $this->installTestRateLimitKey($request);
        $maxAttempts = 5;

        if (RateLimiter::tooManyAttempts($attemptKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($attemptKey);
            $minutes = (int) ceil($seconds / 60);

            Log::channel('installer')->warning('Installer test connection locked out.', [
                'ip' => $request->ip(),
                'available_in_seconds' => $seconds,
            ]);

            return back()
                ->withErrors([
                    'db_connection' => "Terlalu banyak percobaan tes koneksi. Coba lagi dalam {$minutes} menit.",
                ])
                ->with('install_test_lockout_seconds', $seconds)
                ->withInput();
        }

        $validated = $request->validate([
            'db_connection' => ['required', 'in:pgsql,mysql'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->testConnection(
                $validated['db_connection'],
                $validated['db_host'],
                (int) $validated['db_port'],
                $validated['db_database'],
                $validated['db_username'],
                $validated['db_password'] ?? ''
            );
        } catch (Throwable $exception) {
            report($exception);
            RateLimiter::hit($attemptKey, 600);
            Log::channel('installer')->warning('Installer test connection failed.', [
                'ip' => $request->ip(),
                'db_connection' => $validated['db_connection'],
                'db_host' => $validated['db_host'],
                'db_port' => (int) $validated['db_port'],
                'db_database' => $validated['db_database'],
                'db_username' => $validated['db_username'],
            ]);

            return back()
                ->withErrors(['db_connection' => $this->connectionFailedMessage()])
                ->withInput();
        }

        RateLimiter::clear($attemptKey);
        Log::channel('installer')->info('Installer test connection succeeded.', [
            'ip' => $request->ip(),
            'db_connection' => $validated['db_connection'],
            'db_host' => $validated['db_host'],
            'db_port' => (int) $validated['db_port'],
            'db_database' => $validated['db_database'],
            'db_username' => $validated['db_username'],
        ]);

        return back()->with('status', 'Tes koneksi berhasil. Konfigurasi database valid.');
    }

    public function resetForm(): View
    {
        return view('settings.installer-reset');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $this->deleteInstallLock();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('install.show')->with('status', 'Installer diaktifkan kembali. Silakan setup ulang database.');
    }

    private function isInstalled(): bool
    {
        return is_file(storage_path('app/install.lock'));
    }

    private function testConnection(
        string $connection,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password
    ): void {
        $dsn = match ($connection) {
            'pgsql' => "pgsql:host={$host};port={$port};dbname={$database}",
            'mysql' => "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            default => throw new RuntimeException('Driver database tidak didukung.'),
        };

        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_TIMEOUT => 5,
        ]);

        $pdo = null;
    }

    /**
     * @param array<string, string> $pairs
     */
    private function updateEnvFile(array $pairs): void
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            throw new RuntimeException('File .env tidak ditemukan.');
        }

        $content = file_get_contents($envPath);
        if ($content === false) {
            throw new RuntimeException('Gagal membaca file .env.');
        }

        foreach ($pairs as $key => $value) {
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $line = $key.'='.$value;

            if (preg_match($pattern, $content) === 1) {
                $content = (string) preg_replace($pattern, $line, $content);
            } else {
                $content .= PHP_EOL.$line;
            }
        }

        if (file_put_contents($envPath, $content) === false) {
            throw new RuntimeException('Gagal menulis perubahan .env. Cek permission file.');
        }
    }

    private function writeInstallLock(): void
    {
        $lockPath = storage_path('app/install.lock');
        $payload = [
            'installed_at' => now()->toDateTimeString(),
            'seed_mode' => 'disabled',
        ];

        if (file_put_contents($lockPath, json_encode($payload, JSON_PRETTY_PRINT)) === false) {
            throw new RuntimeException('Gagal membuat install lock file.');
        }
    }

    private function deleteInstallLock(): void
    {
        $lockPath = storage_path('app/install.lock');

        if (is_file($lockPath) && ! unlink($lockPath)) {
            throw new RuntimeException('Gagal menghapus install lock file.');
        }
    }

    private function connectionFailedMessage(): string
    {
        return 'Koneksi database gagal. Periksa driver, host, port, nama database, username, dan password.';
    }

    private function setupFailedMessage(): string
    {
        return 'Setup gagal dijalankan. Cek kembali konfigurasi dan permission server lalu coba lagi.';
    }

    private function installTestRateLimitKey(Request $request): string
    {
        return 'install-test|'.sha1((string) $request->ip());
    }

    private function generateUniqueUsername(string $name, string $email): string
    {
        $base = Str::of($name)->lower()->slug('_')->value();
        if ($base === '') {
            $base = Str::before($email, '@');
        }

        $base = preg_replace('/[^a-z0-9_]/', '', strtolower($base)) ?: 'user';
        $base = substr($base, 0, 40);

        $candidate = $base;
        $counter = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = substr($base, 0, 40).'_'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
