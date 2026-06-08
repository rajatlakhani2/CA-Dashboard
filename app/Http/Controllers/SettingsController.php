<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Support\Branding;
use App\Support\InvoicePdfData;
use App\Support\ModuleAccess;
use App\Support\ModuleGate;
use App\Support\WorkspaceProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $this->authorize('view', Setting::class);

        return view('settings.profile', array_merge(
            [
                'user' => auth()->user(),
                'firmModules' => ModuleGate::firmModules(),
                'moduleGroups' => ModuleGate::groups(),
                'workspaceType' => WorkspaceProfile::current(),
                'workspaceTypes' => WorkspaceProfile::types(),
                'workspaceDescriptions' => WorkspaceProfile::descriptions(),
                'workspaceRoles' => WorkspaceProfile::roles(),
            ],
            $this->firmSettingDefaults()
        ));
    }

    public function update(Request $request)
    {
        $this->authorize('updateProfile', Setting::class);

        $user = auth()->user();

        $profileData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('organization_id', $user->organization_id))
                    ->ignore($user->id),
            ],
            'theme' => 'required|in:modern,executive,dense',
            'current_password' => 'nullable|required_with:new_password|current_password',
            'new_password' => 'nullable|min:8|confirmed',
            'mobile' => 'required|string|max:20',
        ]);

        $user->name = $profileData['name'];
        $user->email = $profileData['email'];
        $user->theme = $profileData['theme'];
        $user->mobile = $profileData['mobile'];

        if ($request->filled('new_password')) {
            $user->password = Hash::make($profileData['new_password']);
        }

        $user->save();

        if ($request->user()->can('updateFirm', Setting::class)) {
            $firmData = $request->validate($this->firmValidationRules());
            $this->updateFirmSettings($firmData);

            if ($request->has('workspace_type')) {
                $workspaceData = $request->validate([
                    'workspace_type' => 'required|in:ca_firm,executive',
                    'apply_workspace_preset' => 'nullable|boolean',
                ]);
                WorkspaceProfile::saveType($workspaceData['workspace_type']);
                if ($request->boolean('apply_workspace_preset')) {
                    WorkspaceProfile::applyModulePreset($workspaceData['workspace_type']);
                }
            }

            if ($request->has('firm_modules')) {
                ModuleGate::saveFirmModules($request->input('firm_modules', []));
            }
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    public function users()
    {
        $this->authorize('manageUsers', Setting::class);

        $users = User::query()
            ->inOrganization(auth()->user()->organization_id)
            ->orderBy('name')
            ->get();

        return view('settings.users', [
            'users' => $users,
            'modules' => ModuleAccess::MODULES,
            'workspaceType' => WorkspaceProfile::current(),
            'workspaceTypes' => WorkspaceProfile::types(),
            'workspaceRoles' => WorkspaceProfile::roles(),
            'workspaceRoleHints' => WorkspaceProfile::roleHints(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $this->authorize('manageUsers', Setting::class);

        $organizationId = $request->user()->organization_id;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(
                    fn ($q) => $q->where('organization_id', $organizationId)
                ),
            ],
            'mobile' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(array_keys(WorkspaceProfile::roles()))],
            'branch_id' => 'nullable|exists:branches,id',
        ], [
            'email.unique' => 'This email is already used in your firm. Check the Staff Directory tab — or use a different address.',
        ]);

        $organization = $request->user()->organization;
        if ($organization && ! $organization->hasSeatAvailable()) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Seat limit reached (' . $organization->seat_limit . '). Remove a user or upgrade your plan.']);
        }

        User::create([
            'organization_id' => $organizationId,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'mobile' => $data['mobile'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'branch_id' => $data['branch_id'] ?? null,
            'module_access' => ModuleAccess::defaultsForRole($data['role']),
        ]);

        return back()->with('success', 'User account created. They can sign in with email and password.');
    }

    public function updateRole(\App\Http\Requests\UpdateUserRoleRequest $request, User $user, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('manageUsers', Setting::class);

        $previousRole = $user->role;
        $user->role = $request->role;
        $user->mobile = $request->mobile;
        $user->save();

        if ($previousRole !== $user->role) {
            $audit->userRoleChanged($user, $previousRole, $user->role);
        }

        return back()->with('success', 'User updated successfully.');
    }

    public function updateModuleAccess(Request $request, User $user, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('manageUsers', Setting::class);

        if ($user->isWorkspaceOwner()) {
            return back()->with('error', 'Workspace owner access cannot be restricted.');
        }

        $previous = $user->module_access ?? [];

        $firmModules = ModuleGate::firmModules();
        $access = [];
        foreach (array_keys(ModuleAccess::MODULES) as $key) {
            $access[$key] = ($firmModules[$key] ?? false) && $request->boolean("modules.{$key}");
        }

        $user->module_access = $access;
        $user->save();

        $audit->moduleAccessChanged($user, $previous, $access);

        return back()->with('success', "Module access updated for {$user->name}.");
    }

    private function firmSettingDefaults(): array
    {
        $keys = [
            'dashboard_name' => Branding::dashboardName(),
            'dashboard_tagline' => '',
            'company_name' => 'RAJAT LAKHANI & ASSOCIATES',
            'company_tagline' => 'Chartered Accountants',
            'company_address' => "Ahmedabad, Gujarat",
            'company_email' => 'info@rlassociates.in',
            'company_phone' => '',
            'company_website' => '',
            'firm_gstin' => '',
            'firm_pan' => '',
            'firm_state_name' => 'Gujarat',
            'firm_state_code' => '24',
            'default_sac_code' => '998221',
            'default_gst_rate' => '18',
            'reminder_time_1' => '10:00',
            'reminder_time_2' => '18:00',
            'invoice_title' => 'TAX INVOICE',
            'invoice_footer' => '',
            'invoice_terms' => InvoicePdfData::defaultTerms(),
            'invoice_show_gst_breakup' => '1',
            'invoice_number_prefix' => 'RLA/25-26/',
            'invoice_payment_days' => '15',
            'invoice_jurisdiction' => 'Ahmedabad',
            'company_logo_path' => '',
            'bank_name' => '',
            'bank_account_name' => '',
            'bank_account_number' => '',
            'bank_ifsc' => '',
            'bank_upi' => '',
            'invoice_signatory_name' => '',
            'auto_backup_enabled' => '1',
            'auto_logout_minutes' => '0',
        ];

        $out = [];
        foreach ($keys as $key => $default) {
            $out[$key] = Setting::get($key, $default);
        }

        return $out;
    }

    private function firmValidationRules(): array
    {
        return [
            'dashboard_name' => 'nullable|string|max:80',
            'dashboard_tagline' => 'nullable|string|max:120',
            'company_name' => 'nullable|string|max:255',
            'company_tagline' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_email' => 'nullable|email',
            'company_phone' => 'nullable|string|max:30',
            'company_website' => 'nullable|string|max:255',
            'firm_gstin' => 'nullable|string|max:15',
            'firm_pan' => 'nullable|string|max:10',
            'firm_state_name' => 'nullable|string|max:100',
            'firm_state_code' => 'nullable|string|max:2',
            'default_sac_code' => 'nullable|string|max:10',
            'default_gst_rate' => 'nullable|numeric|min:0|max:28',
            'reminder_time_1' => 'nullable|date_format:H:i',
            'reminder_time_2' => 'nullable|date_format:H:i',
            'invoice_title' => 'nullable|string|max:100',
            'invoice_footer' => 'nullable|string|max:500',
            'invoice_terms' => 'nullable|string|max:2000',
            'invoice_show_gst_breakup' => 'nullable|in:0,1',
            'invoice_number_prefix' => 'nullable|string|max:30',
            'invoice_payment_days' => 'nullable|string|max:10',
            'invoice_jurisdiction' => 'nullable|string|max:100',
            'company_logo_path' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:30',
            'bank_ifsc' => 'nullable|string|max:20',
            'bank_upi' => 'nullable|string|max:100',
            'invoice_signatory_name' => 'nullable|string|max:255',
            'auto_backup_enabled' => 'nullable|in:0,1',
            'auto_logout_minutes' => 'nullable|integer|in:0,15,30,60,120,240,480',
        ];
    }

    private function updateFirmSettings(array $firmData): void
    {
        foreach (array_keys($this->firmValidationRules()) as $key) {
            if (array_key_exists($key, $firmData)) {
                Setting::set($key, $firmData[$key] ?? '');
            }
        }
    }
}
