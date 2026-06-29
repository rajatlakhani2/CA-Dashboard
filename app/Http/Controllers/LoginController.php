<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Support\DemoWorkspace;
use App\Support\ThemePreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show(Request $request)
    {
        if (session('session_expired')) {
            $request->session()->regenerateToken();
        }

        $workspace = old('workspace', session('workspace_slug', request('workspace')));

        return view('auth.login', [
            'workspace' => $workspace,
            'themePreset' => ThemePreset::resolveForLogin($workspace),
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'workspace' => ['required', 'string', 'max:48'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $slug = strtolower(trim($validated['workspace']));
        $organization = Organization::where('slug', $slug)->where('is_active', true)->first();

        if (! $organization) {
            throw ValidationException::withMessages([
                'workspace' => 'Workspace not found. Check the ID from your firm admin or registration email.',
            ]);
        }

        $user = User::where('organization_id', $organization->id)
            ->where('email', strtolower($validated['email']))
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records for this workspace.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('workspace_slug', $organization->slug);

        if (DemoWorkspace::isDemoOrganization($organization)) {
            $user->forceFill(['demo_tour_completed_at' => null])->save();
            $request->session()->forget('demo_tour_dismissed');
            $request->session()->put('demo_tour_pending', true);
            $request->session()->flash('demo_tour_fresh_start', true);
        }

        if (DemoWorkspace::isDemoOrganization($organization)) {
            return redirect()->intended(route('dashboard'));
        }

        if ($user->isArticle() && $user->canAccessModule('tasks')) {
            return redirect()->intended(route('tasks.index'));
        }

        if ($user->canAccessModule('dashboard')) {
            return redirect()->intended(route('dashboard'));
        }

        if ($user->canAccessModule('tasks')) {
            return redirect()->intended(route('tasks.index'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
