<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->organization_id) {
            return $next($request);
        }

        $organization = Organization::find($user->organization_id);
        if ($organization && ! $organization->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['workspace' => 'This firm workspace has been suspended. Contact support.']);
        }

        if ($organization) {
            $request->session()->put('workspace_slug', $organization->slug);
        }

        OrganizationContext::set($user->organization_id);

        return $next($request);
    }
}
