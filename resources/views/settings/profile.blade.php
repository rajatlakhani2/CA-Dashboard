@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>User Settings</span>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6 space-y-6">

                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Profile Information</h3>
                    <p class="mt-1 text-sm text-gray-500">Update your account's profile information and email address.</p>
                </div>

                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <label for="mobile" class="block text-sm font-medium text-gray-700">Mobile Number (For WhatsApp Reminders)</label>
                        <input type="text" name="mobile" id="mobile" value="{{ old('mobile', $user->mobile) }}" required placeholder="e.g. 919876543210 (required for daily task reminders)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <label for="theme" class="block text-sm font-medium text-gray-700">Dashboard Theme</label>
                        <select id="theme" name="theme" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="modern" {{ $user->theme == 'modern' ? 'selected' : '' }}>Modern</option>
                            <option value="executive" {{ $user->theme == 'executive' ? 'selected' : '' }}>Executive</option>
                            <option value="dense" {{ $user->theme == 'dense' ? 'selected' : '' }}>Dense</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">Select your preferred color scheme and density.</p>
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                        <select id="timezone" name="timezone" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" @selected(old('timezone', $user->timezone) === null || old('timezone', $user->timezone) === '')>
                                Firm default ({{ config('app.timezone') ?: 'Asia/Kolkata' }})
                            </option>
                            @foreach(\App\Support\UserTimezone::selectOptions() as $tzId => $tzLabel)
                            <option value="{{ $tzId }}" @selected(old('timezone', $user->timezone) === $tzId)>{{ $tzLabel }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-sm text-gray-500">Used for “due today”, “due tomorrow”, and calendar KPIs on your dashboard.</p>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Look & feel</h3>
                            <p class="mt-1 text-sm text-gray-500">Not happy with the current font or colours? Preview five alternatives side by side.</p>
                        </div>
                        <a href="{{ route('demo.themes') }}" target="_blank" rel="noopener"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 shrink-0">
                            Open theme gallery →
                        </a>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Interface Settings</h3>
                        <p class="mt-1 text-sm text-gray-500">Customize the look and feel of your dashboard.</p>
                    </div>

                    <div class="grid grid-cols-6 gap-6 mt-6" x-data="{ 
                        scale: localStorage.getItem('app_scale') || 100,
                        updateScale(val) {
                            this.scale = val;
                            document.documentElement.style.fontSize = val + '%';
                            localStorage.setItem('app_scale', val);
                        }
                    }">
                        <div class="col-span-6 sm:col-span-4">
                            <label for="theme" class="block text-sm font-medium text-gray-700">Dashboard Theme</label>
                            <select id="theme" name="theme" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="modern" {{ $user->theme == 'modern' ? 'selected' : '' }}>Modern</option>
                                <option value="executive" {{ $user->theme == 'executive' ? 'selected' : '' }}>Executive</option>
                                <option value="dense" {{ $user->theme == 'dense' ? 'selected' : '' }}>Dense</option>
                            </select>
                            <p class="mt-2 text-sm text-gray-500">Controls the color density and spacing.</p>
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label for="font_scale" class="block text-sm font-medium text-gray-700">
                                Font Size: <span x-text="scale + '%'"></span>
                            </label>
                            <input
                                type="range"
                                id="font_scale"
                                min="85"
                                max="115"
                                step="5"
                                x-model="scale"
                                @input="updateScale($event.target.value)"
                                class="w-full mt-2 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>Small</span>
                                <span>Default</span>
                                <span>Large</span>
                            </div>
                        </div>
                    </div>
                </div>

                @can('updateFirm', \App\Models\Setting::class)
                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Dashboard name</h3>
                        <p class="mt-1 text-sm text-gray-500">This name appears in the sidebar, browser tab, and login screen. It is separate from your legal firm name on invoices.</p>
                    </div>
                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-4">
                            <label for="dashboard_name" class="block text-sm font-medium text-gray-700">Display name</label>
                            <input type="text" name="dashboard_name" id="dashboard_name" value="{{ old('dashboard_name', $dashboard_name ?? \App\Support\Branding::DEFAULT_NAME) }}" placeholder="e.g. Vouchex" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <p class="mt-1.5 text-xs text-gray-500">Sidebar preview: <span class="font-semibold text-gray-800">{{ old('dashboard_name', $dashboard_name ?? \App\Support\Branding::DEFAULT_NAME) }}</span></p>
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <label for="dashboard_tagline" class="block text-sm font-medium text-gray-700">Login tagline <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="dashboard_tagline" id="dashboard_tagline" value="{{ old('dashboard_tagline', $dashboard_tagline ?? '') }}" placeholder="e.g. Finance & compliance workspace" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Workspace Profile</h3>
                        <p class="mt-1 text-sm text-gray-500">Choose who uses this dashboard. This controls which roles appear when you create users and the recommended module preset.</p>
                    </div>
                    <div class="mt-6 space-y-4">
                        @foreach($workspaceTypes as $typeKey => $typeLabel)
                        <label class="flex items-start gap-3 rounded-xl border p-4 cursor-pointer transition {{ ($workspaceType ?? 'ca_firm') === $typeKey ? 'border-indigo-400 bg-indigo-50/60' : 'border-gray-200 hover:border-indigo-200' }}">
                            <input type="radio" name="workspace_type" value="{{ $typeKey }}" class="mt-1 text-indigo-600"
                                {{ ($workspaceType ?? 'ca_firm') === $typeKey ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-semibold text-gray-900">{{ $typeLabel }}</span>
                                <span class="block text-xs text-gray-500 mt-0.5">{{ $workspaceDescriptions[$typeKey] ?? '' }}</span>
                                <span class="block text-xs text-indigo-700 mt-1 font-medium">Roles: {{ implode(', ', array_values(\App\Support\WorkspaceProfile::roles($typeKey))) }}</span>
                            </span>
                        </label>
                        @endforeach
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="apply_workspace_preset" value="1" checked class="rounded border-gray-300 text-indigo-600">
                            Apply recommended module preset when saving (you can still fine-tune modules below)
                        </label>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Firm Modules</h3>
                        <p class="mt-1 text-sm text-gray-500">Turn off modules your firm does not use (e.g. hide Invoices for CEO/CFO-only workspaces). Dashboard and Settings always stay on.</p>
                    </div>
                    <div class="mt-6 space-y-5">
                        @foreach($moduleGroups as $groupLabel => $moduleKeys)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">{{ $groupLabel }}</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach($moduleKeys as $moduleKey)
                                @php $locked = in_array($moduleKey, \App\Support\ModuleGate::FIRM_ALWAYS_ON, true); @endphp
                                <label class="flex items-center gap-2 text-sm text-slate-700 bg-white rounded-lg px-3 py-2 border border-slate-200 {{ $locked ? 'opacity-60' : 'cursor-pointer hover:border-indigo-200' }}">
                                    <input type="checkbox" name="firm_modules[{{ $moduleKey }}]" value="1"
                                        {{ ($firmModules[$moduleKey] ?? true) ? 'checked' : '' }}
                                        {{ $locked ? 'checked disabled' : '' }}
                                        class="rounded border-slate-300 text-indigo-600">
                                    <span>{{ \App\Support\ModuleAccess::MODULES[$moduleKey] ?? $moduleKey }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Company Details</h3>
                        <p class="mt-1 text-sm text-gray-500">These details will appear on your Invoices.</p>
                    </div>

                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-4">
                            <label for="company_name" class="block text-sm font-medium text-gray-700">Firm / Business Name</label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $company_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <label for="company_tagline" class="block text-sm font-medium text-gray-700">Tagline</label>
                            <input type="text" name="company_tagline" id="company_tagline" value="{{ old('company_tagline', $company_tagline ?? '') }}" placeholder="Chartered Accountants" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <label for="company_logo_path" class="block text-sm font-medium text-gray-700">Logo path (under public/)</label>
                            <input type="text" name="company_logo_path" id="company_logo_path" value="{{ old('company_logo_path', $company_logo_path ?? '') }}" placeholder="images/logo.png" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6">
                            <label for="company_address" class="block text-sm font-medium text-gray-700">Office Address</label>
                            <textarea name="company_address" id="company_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('company_address', $company_address) }}</textarea>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="company_phone" class="block text-sm font-medium text-gray-700">Contact Number</label>
                            <input type="text" name="company_phone" id="company_phone" value="{{ old('company_phone', $company_phone ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="company_email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="company_email" id="company_email" value="{{ old('company_email', $company_email) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="company_website" class="block text-sm font-medium text-gray-700">Website</label>
                            <input type="text" name="company_website" id="company_website" value="{{ old('company_website', $company_website ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="firm_gstin" class="block text-sm font-medium text-gray-700">GSTIN</label>
                            <input type="text" name="firm_gstin" id="firm_gstin" value="{{ old('firm_gstin', $firm_gstin ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="firm_pan" class="block text-sm font-medium text-gray-700">PAN</label>
                            <input type="text" name="firm_pan" id="firm_pan" value="{{ old('firm_pan', $firm_pan ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="firm_state_name" class="block text-sm font-medium text-gray-700">State</label>
                            <input type="text" name="firm_state_name" id="firm_state_name" value="{{ old('firm_state_name', $firm_state_name ?? 'Gujarat') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label for="firm_state_code" class="block text-sm font-medium text-gray-700">State Code</label>
                            <input type="text" name="firm_state_code" id="firm_state_code" value="{{ old('firm_state_code', $firm_state_code ?? '24') }}" maxlength="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label for="default_sac_code" class="block text-sm font-medium text-gray-700">Default SAC (CA services)</label>
                            <input type="text" name="default_sac_code" id="default_sac_code" value="{{ old('default_sac_code', $default_sac_code ?? '998221') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label for="default_gst_rate" class="block text-sm font-medium text-gray-700">Default GST %</label>
                            <input type="number" name="default_gst_rate" id="default_gst_rate" value="{{ old('default_gst_rate', $default_gst_rate ?? '18') }}" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Bank Details (on invoice PDF)</h3>
                        <p class="mt-1 text-sm text-gray-500">Shown on invoices for payment collection.</p>
                    </div>
                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-3">
                            <label for="bank_name" class="block text-sm font-medium text-gray-700">Bank Name</label>
                            <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $bank_name ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="bank_account_name" class="block text-sm font-medium text-gray-700">Account Name</label>
                            <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $bank_account_name ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="bank_account_number" class="block text-sm font-medium text-gray-700">Account Number</label>
                            <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $bank_account_number ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="bank_ifsc" class="block text-sm font-medium text-gray-700">IFSC</label>
                            <input type="text" name="bank_ifsc" id="bank_ifsc" value="{{ old('bank_ifsc', $bank_ifsc ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <label for="bank_upi" class="block text-sm font-medium text-gray-700">UPI ID (optional)</label>
                            <input type="text" name="bank_upi" id="bank_upi" value="{{ old('bank_upi', $bank_upi ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Invoice PDF Format</h3>
                        <p class="mt-1 text-sm text-gray-500">Controls the downloaded invoice PDF layout and wording.</p>
                    </div>
                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-4">
                            <label for="invoice_title" class="block text-sm font-medium text-gray-700">Invoice heading</label>
                            <input type="text" name="invoice_title" id="invoice_title" value="{{ old('invoice_title', $invoice_title ?? 'TAX INVOICE') }}" placeholder="TAX INVOICE / GST TAX INVOICE" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <label for="invoice_number_prefix" class="block text-sm font-medium text-gray-700">Suggested number prefix</label>
                            <input type="text" name="invoice_number_prefix" id="invoice_number_prefix" value="{{ old('invoice_number_prefix', $invoice_number_prefix ?? 'RLA/25-26/') }}" placeholder="RLA/25-26/ or INV/2025-26/" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Reference only — enter full numbers when creating invoices.</p>
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label for="invoice_payment_days" class="block text-sm font-medium text-gray-700">Payment due (days)</label>
                            <input type="text" name="invoice_payment_days" id="invoice_payment_days" value="{{ old('invoice_payment_days', $invoice_payment_days ?? '15') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label for="invoice_jurisdiction" class="block text-sm font-medium text-gray-700">Jurisdiction</label>
                            <input type="text" name="invoice_jurisdiction" id="invoice_jurisdiction" value="{{ old('invoice_jurisdiction', $invoice_jurisdiction ?? 'Ahmedabad') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <label for="invoice_signatory_name" class="block text-sm font-medium text-gray-700">Authorized signatory name (on PDF)</label>
                            <input type="text" name="invoice_signatory_name" id="invoice_signatory_name" value="{{ old('invoice_signatory_name', $invoice_signatory_name ?? '') }}" placeholder="e.g. Rajat Lakhani" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Printed above the signature line. Leave blank for a blank signature space.</p>
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <label for="invoice_show_gst_breakup" class="block text-sm font-medium text-gray-700">Show GST columns on PDF</label>
                            <select name="invoice_show_gst_breakup" id="invoice_show_gst_breakup" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                <option value="1" {{ ($invoice_show_gst_breakup ?? '1') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ ($invoice_show_gst_breakup ?? '1') == '0' ? 'selected' : '' }}>No — subtotal + tax only</option>
                            </select>
                        </div>
                        <div class="col-span-6">
                            <label for="invoice_footer" class="block text-sm font-medium text-gray-700">Footer note</label>
                            <textarea name="invoice_footer" id="invoice_footer" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('invoice_footer', $invoice_footer ?? '') }}</textarea>
                        </div>
                        <div class="col-span-6">
                            <label for="invoice_terms" class="block text-sm font-medium text-gray-700">Terms & conditions</label>
                            <textarea name="invoice_terms" id="invoice_terms" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('invoice_terms', $invoice_terms ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Security &amp; data</h3>
                        <p class="mt-1 text-sm text-gray-500">Automatic sign-out and nightly backups (requires server cron: <code class="text-xs bg-gray-100 px-1 rounded">php artisan schedule:run</code>).</p>
                    </div>
                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-3">
                            <label for="auto_logout_minutes" class="block text-sm font-medium text-gray-700">Auto logout after inactivity</label>
                            <select name="auto_logout_minutes" id="auto_logout_minutes" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                @foreach(['0' => 'Disabled', '15' => '15 minutes', '30' => '30 minutes', '60' => '1 hour', '120' => '2 hours', '240' => '4 hours', '480' => '8 hours'] as $val => $label)
                                <option value="{{ $val }}" {{ (string) old('auto_logout_minutes', $auto_logout_minutes ?? '0') === (string) $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="auto_backup_enabled" class="block text-sm font-medium text-gray-700">Automatic nightly backup</label>
                            <select name="auto_backup_enabled" id="auto_backup_enabled" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                <option value="1" {{ ($auto_backup_enabled ?? '1') == '1' ? 'selected' : '' }}>Enabled (daily)</option>
                                <option value="0" {{ ($auto_backup_enabled ?? '1') == '0' ? 'selected' : '' }}>Disabled</option>
                            </select>
                            <p class="mt-2 text-xs text-gray-500">Manual backup: <a href="{{ route('system.index') }}" class="text-indigo-600 hover:underline">System Health</a>.</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">WhatsApp Reminder Settings</h3>
                        <p class="mt-1 text-sm text-gray-500">Configure when the daily pending task reminders are sent to the team.</p>
                    </div>

                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-3">
                            <label for="reminder_time_1" class="block text-sm font-medium text-gray-700">Morning Reminder Time</label>
                            <input type="time" name="reminder_time_1" id="reminder_time_1" value="{{ old('reminder_time_1', $reminder_time_1 ?? '10:00') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <label for="reminder_time_2" class="block text-sm font-medium text-gray-700">Evening Reminder Time</label>
                            <input type="time" name="reminder_time_2" id="reminder_time_2" value="{{ old('reminder_time_2', $reminder_time_2 ?? '18:00') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
                @endcan

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Update Password</h3>
                        <p class="mt-1 text-sm text-gray-500">Ensure your account is using a long, random password to stay secure.</p>
                    </div>

                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-4">
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

            </div>
            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
