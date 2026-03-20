<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            abort(401);
        }

        if ($user->is_active === false) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Account is inactive.'], 403);
            }

            abort(403, 'Akun nonaktif. Hubungi owner.');
        }

        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
