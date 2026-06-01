<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictArticleAccess
{
    /**
     * Article clerks: tasks + submit new clients (no client list); Rajat approves before firm-wide visibility.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isArticle()) {
            return $next($request);
        }

        if ($request->routeIs(
            'tasks.index',
            'tasks.my-day',
            'tasks.update-status',
            'search.global',
            'clients.create',
            'clients.store',
            'logout',
        )) {
            return $next($request);
        }

        return redirect()->route('tasks.index');
    }
}
