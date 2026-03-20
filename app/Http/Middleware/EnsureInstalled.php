<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installedLockPath = storage_path('app/install.lock');

        if (! is_file($installedLockPath)) {
            return redirect()->route('install.show');
        }

        return $next($request);
    }
}
