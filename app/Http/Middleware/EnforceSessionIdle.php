<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionIdle
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $minutes = (int) Setting::get('auto_logout_minutes', 0);

        if ($minutes <= 0) {
            session(['last_activity_at' => now()->timestamp]);

            return $next($request);
        }

        $last = (int) session('last_activity_at', 0);
        $now = now()->timestamp;

        if ($last > 0 && ($now - $last) > ($minutes * 60)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'You were signed out after a period of inactivity.');
        }

        session(['last_activity_at' => $now]);

        return $next($request);
    }
}
