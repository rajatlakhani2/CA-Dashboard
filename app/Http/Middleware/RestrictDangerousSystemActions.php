<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDangerousSystemActions
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ! config('app.allow_dangerous_system_actions', false)) {
            abort(403, 'This action is disabled in production. Enable APP_ALLOW_DANGEROUS_SYSTEM only when required.');
        }

        return $next($request);
    }
}
