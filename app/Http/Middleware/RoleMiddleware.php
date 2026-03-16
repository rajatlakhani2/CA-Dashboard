<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        if (!$user || !$user->hasRole(...$roles)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
