<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform-wide pages (System Health, full-database backups, logs, cache,
 * migrations) affect every firm on the server, so a firm's own partner is
 * not enough. See config('app.platform_admin_workspace').
 */
class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403, 'Only platform administrators can open this page.');

        return $next($request);
    }
}
