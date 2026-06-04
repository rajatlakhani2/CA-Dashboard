<?php

namespace App\Http\Controllers;

use App\Services\OrganizationRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterOrganizationController extends Controller
{
    public function show()
    {
        return view('auth.register-organization');
    }

    public function store(Request $request, OrganizationRegistrationService $registration)
    {
        $validated = $request->validate([
            'firm_name' => ['required', 'string', 'max:255'],
            'workspace' => [
                'required',
                'string',
                'max:48',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('organizations', 'slug'),
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'workspace.regex' => 'Workspace ID: lowercase letters, numbers, and hyphens only (e.g. sharma-ca).',
            'workspace.unique' => 'This workspace ID is already taken. Choose another.',
        ]);

        $organization = $registration->register([
            'firm_name' => $validated['firm_name'],
            'workspace' => $validated['workspace'],
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
        ]);

        $user = $organization->users()->where('email', strtolower($validated['admin_email']))->first();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('workspace_slug', $organization->slug);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome! Your firm workspace "' . $organization->name . '" is ready.');
    }
}
