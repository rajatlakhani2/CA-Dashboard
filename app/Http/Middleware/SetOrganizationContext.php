<?php

namespace App\Http\Middleware;

use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        OrganizationContext::clear();

        $user = $request->user();
        if ($user?->organization_id) {
            OrganizationContext::set((int) $user->organization_id);
        }

        return $next($request);
    }
}
