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
            'firm_gstin' => \App\Models\Setting::get('firm_gstin', ''),
            'firm_state_code' => \App\Models\Setting::get('firm_state_code', ''),
            'default_sac_code' => \App\Models\Setting::get('default_sac_code', '998231'),
            'default_gst_rate' => \App\Models\Setting::get('default_gst_rate', '18'),
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
            'firm_gstin' => 'nullable|string|max:15',
            'firm_state_code' => 'nullable|string|max:2',
            'default_sac_code' => 'nullable|string|max:10',
            'default_gst_rate' => 'nullable|numeric|min:0|max:28',
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
        // GST Settings
        \App\Models\Setting::set('firm_gstin', $request->input('firm_gstin', ''));
        \App\Models\Setting::set('firm_state_code', $request->input('firm_state_code', ''));
        \App\Models\Setting::set('default_sac_code', $request->input('default_sac_code', '998231'));
        \App\Models\Setting::set('default_gst_rate', $request->input('default_gst_rate', '18'));

        return back()->with('success', 'Settings updated successfully.');
    }

    public function users()
    {
        $users = \App\Models\User::all();
        return view('settings.users', compact('users'));
    }

    public function updateRole(Request $request, \App\Models\User $user)
    {
        $request->validate([
            'role' => 'required|in:Partner,Manager,Staff,Intern',
        ]);

        $user->role = $request->role;
        $user->save();

        return back()->with('success', 'User role updated successfully.');
    }
}
