<?php

namespace App\Http\Middleware;

use App\Support\SessionIdle;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a user out on the server after the firm's inactivity limit.
 * The browser shows a countdown warning first (see partials/idle-logout);
 * this is the safety net for closed tabs and phones that went to sleep.
 * Automatic background refreshes do not count as activity.
 */
class EnforceSessionIdle
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $limit = SessionIdle::minutesFor(auth()->user()) * 60;
        $last = (int) session('last_activity_at', 0);
        $now = now()->timestamp;

        if ($last > 0 && ($now - $last) > $limit) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Signed out after inactivity.', 'signed_out' => true], 401);
            }

            return redirect()->route('login')
                ->with('status', 'You were signed out after a period of inactivity.');
        }

        if (! $request->headers->has('X-Background-Refresh') || $last === 0) {
            session(['last_activity_at' => $now]);
        }

        return $next($request);
    }
}
