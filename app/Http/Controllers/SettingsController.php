<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.profile', [
            'user' => auth()->user(),
            'company_name' => \App\Models\Setting::get('company_name', 'CA Dashboard Corp'),
            'company_address' => \App\Models\Setting::get('company_address', '123 Business Street, Tech City'),
            'company_email' => \App\Models\Setting::get('company_email', 'billing@cadashboard.com'),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'theme' => 'required|in:modern,executive,dense',
            'current_password' => 'nullable|required_with:new_password|current_password',
            'new_password' => 'nullable|min:8|confirmed',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_email' => 'nullable|email',
        ]);

        // Update User Profile
        $user->name = $request->name;
        $user->email = $request->email;
        $user->theme = $request->theme;

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        // Update Company Settings
        if ($request->filled('company_name')) {
            \App\Models\Setting::set('company_name', $request->company_name);
        }
        if ($request->filled('company_address')) {
            \App\Models\Setting::set('company_address', $request->company_address);
        }
        if ($request->filled('company_email')) {
            \App\Models\Setting::set('company_email', $request->company_email);
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
