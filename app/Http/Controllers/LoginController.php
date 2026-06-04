<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login', [
            'workspace' => old('workspace', session('workspace_slug')),
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

        if ($user->prefersMyDayHome() && $user->canAccessModule('tasks')) {
            return redirect()->intended(route('tasks.my-day'));
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
