<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Support\ThemePreset;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(Request $request)
    {
        $workspace = old('workspace', session('workspace_slug', $request->query('workspace')));

        return view('auth.forgot-password', [
            'workspace' => $workspace,
            'themePreset' => ThemePreset::resolveForLogin($workspace),
        ]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'workspace' => ['required', 'string', 'max:48'],
            'email' => ['required', 'email'],
        ]);

        $slug = strtolower(trim($validated['workspace']));
        $organization = Organization::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $organization) {
            return back()
                ->withInput()
                ->withErrors([
                    'workspace' => 'Workspace not found. Check the ID from your firm admin.',
                ]);
        }

        $user = User::query()
            ->where('organization_id', $organization->id)
            ->where('email', strtolower($validated['email']))
            ->first();

        if ($user) {
            Password::broker()->sendResetLink(
                ['email' => $user->email],
                function (User $user, string $token) use ($organization): void {
                    $user->sendPasswordResetNotification($token, $organization->slug);
                }
            );
        }

        return back()
            ->withInput($request->only('workspace', 'email'))
            ->with('status', 'If an account exists for that workspace and email, we sent a password reset link.');
    }

    public function showResetForm(Request $request, string $token)
    {
        $workspace = old('workspace', $request->query('workspace'));

        return view('auth.reset-password', [
            'token' => $token,
            'email' => old('email', $request->query('email')),
            'workspace' => $workspace,
            'themePreset' => ThemePreset::resolveForLogin($workspace),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'workspace' => ['required', 'string', 'max:48'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $slug = strtolower(trim($validated['workspace']));
        $organization = Organization::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $organization) {
            return back()
                ->withInput($request->only('workspace', 'email'))
                ->withErrors(['workspace' => 'Workspace not found.']);
        }

        $user = User::query()
            ->where('organization_id', $organization->id)
            ->where('email', strtolower($validated['email']))
            ->first();

        if (! $user) {
            return back()
                ->withInput($request->only('workspace', 'email'))
                ->withErrors(['email' => 'This reset link is not valid for the workspace and email provided.']);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $resetUser, string $password) use ($user): void {
                if ((int) $resetUser->id !== (int) $user->id) {
                    return;
                }

                $resetUser->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($resetUser));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login', ['workspace' => $slug])
                ->with('status', 'Your password was updated. Sign in with your new password.');
        }

        return back()
            ->withInput($request->only('workspace', 'email'))
            ->withErrors(['email' => __($status)]);
    }
}
