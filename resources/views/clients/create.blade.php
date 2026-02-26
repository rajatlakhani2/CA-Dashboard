@extends('layouts.app')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('clients.index') }}" class="text-text-secondary hover:text-text-main">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
    </a>
    <span>Add New Client</span>
</div>
@endsection

@section('content')
<div class="bg-bg-card shadow rounded-lg">
    <!-- Form Header/Tabs -->
    <div class="border-b border-line">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs" id="formTabs">
            <button type="button" onclick="switchTab('basic')" class="tab-btn border-primary-500 text-primary-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" id="tab-basic">
                Basic Info
            </button>
            <button type="button" onclick="switchTab('contact')" class="tab-btn border-transparent text-text-secondary hover:border-line hover:text-text-main whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" id="tab-contact">
                Contact & Address
            </button>
            <button type="button" onclick="switchTab('engagement')" class="tab-btn border-transparent text-text-secondary hover:border-line hover:text-text-main whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" id="tab-engagement">
                Engagement & Billing
            </button>
            <button type="button" onclick="switchTab('services')" class="tab-btn border-transparent text-text-secondary hover:border-line hover:text-text-main whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" id="tab-services">
                Services
            </button>
        </nav>
    </div>

    <form action="{{ route('clients.store') }}" method="POST" class="p-6">
        @csrf

        <!-- Tab 1: Basic Info -->
        <div id="panel-basic" class="tab-panel space-y-6">
            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <!-- Name -->
                <div class="sm:col-span-3">
                    <label for="name" class="block text-sm font-medium leading-6 text-text-main">Client Name *</label>
                    <div class="mt-2">
                        <input type="text" name="name" id="name" required class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- Entity Type -->
                <div class="sm:col-span-3">
                    <label for="entity_type" class="block text-sm font-medium leading-6 text-text-main">Entity Type</label>
                    <div class="mt-2">
                        <select name="entity_type" id="entity_type" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            <option>Individual</option>
                            <option>Proprietorship</option>
                            <option>Partnership</option>
                            <option>LLP</option>
                            <option>Private Limited</option>
                            <option>Public Limited</option>
                        </select>
                    </div>
                </div>

                <!-- PAN -->
                <div class="sm:col-span-3">
                    <label for="pan" class="block text-sm font-medium leading-6 text-text-main">PAN Number *</label>
                    <div class="mt-2">
                        <input type="text" name="pan" id="pan" required class="uppercase block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- GSTIN -->
                <div class="sm:col-span-3">
                    <label for="gstin" class="block text-sm font-medium leading-6 text-text-main">GSTIN</label>
                    <div class="mt-2">
                        <input type="text" name="gstin" id="gstin" class="uppercase block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- Industry -->
                <div class="sm:col-span-3">
                    <label for="industry" class="block text-sm font-medium leading-6 text-text-main">Industry</label>
                    <div class="mt-2">
                        <input type="text" name="industry" id="industry" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- Status -->
                <div class="sm:col-span-3">
                    <label for="status" class="block text-sm font-medium leading-6 text-text-main">Status *</label>
                    <div class="mt-2">
                        <select name="status" id="status" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            <option value="Active">Active</option>
                            <option value="On-Hold">On-Hold</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>

                <!-- Tags -->
                <div class="sm:col-span-6">
                    <label for="tags" class="block text-sm font-medium leading-6 text-text-main">Tags (comma separated)</label>
                    <div class="mt-2">
                        <input type="text" name="tags" id="tags" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" onclick="switchTab('contact')" class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">Next: Contact Info</button>
            </div>
        </div>

        <!-- Tab 2: Contact Info -->
        <div id="panel-contact" class="tab-panel hidden space-y-6">
            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <!-- Primary Contact -->
                <div class="sm:col-span-6">
                    <h3 class="text-base font-semibold leading-7 text-text-main">Primary Contact Person</h3>
                </div>

                <div class="sm:col-span-2">
                    <label for="primary_contact_name" class="block text-sm font-medium leading-6 text-text-main">Name</label>
                    <div class="mt-2">
                        <input type="text" name="primary_contact_name" id="primary_contact_name" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="primary_contact_phone" class="block text-sm font-medium leading-6 text-text-main">Phone</label>
                    <div class="mt-2">
                        <input type="text" name="primary_contact_phone" id="primary_contact_phone" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="primary_contact_email" class="block text-sm font-medium leading-6 text-text-main">Email</label>
                    <div class="mt-2">
                        <input type="email" name="primary_contact_email" id="primary_contact_email" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div class="sm:col-span-6 border-t pt-4">
                    <h3 class="text-base font-semibold leading-7 text-text-main">Address Details</h3>
                </div>

                <div class="sm:col-span-3">
                    <label for="registered_address" class="block text-sm font-medium leading-6 text-text-main">Registered Address</label>
                    <div class="mt-2">
                        <textarea name="registered_address" id="registered_address" rows="3" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-between">
                <button type="button" onclick="switchTab('basic')" class="text-sm font-semibold text-text-main">Back</button>
                <button type="button" onclick="switchTab('engagement')" class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">Next: Engagement</button>
            </div>
        </div>

        <!-- Tab 3: Engagement -->
        <div id="panel-engagement" class="tab-panel hidden space-y-6">
            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <!-- Category -->
                <div class="sm:col-span-2">
                    <label for="category" class="block text-sm font-medium leading-6 text-text-main">Client Category *</label>
                    <div class="mt-2">
                        <select name="category" id="category" required class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            <option value="A">Category A (Premium)</option>
                            <option value="B">Category B (Standard)</option>
                            <option value="C" selected>Category C (Basic)</option>
                        </select>
                    </div>
                </div>

                <!-- Billing Cycle -->
                <div class="sm:col-span-2">
                    <label for="billing_cycle" class="block text-sm font-medium leading-6 text-text-main">Billing Cycle</label>
                    <div class="mt-2">
                        <select name="billing_cycle" id="billing_cycle" class="block w-full rounded-md border-0 py-1.5 text-text-main shadow-sm ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            <option>Monthly</option>
                            <option>Quarterly</option>
                            <option>Annual</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-between border-t pt-6">
                <button type="button" onclick="switchTab('contact')" class="text-sm font-semibold text-text-main">Back</button>
                <button type="button" onclick="switchTab('services')" class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">Next: Services</button>
            </div>
        </div>

        <!-- Tab 4: Services -->
        <div id="panel-services" class="tab-panel hidden space-y-6">
            <div class="border-b border-line pb-4">
                <h3 class="text-base font-semibold leading-6 text-text-main">Opted Services</h3>
                <p class="mt-2 text-sm text-text-secondary">Select the recurring compliance services for this client.</p>
            </div>

            <fieldset>
                <div class="space-y-4">
                    @foreach($services as $service)
                    <div class="relative flex items-center py-2">
                        <div class="flex h-6 items-center">
                            <input id="service-{{ $service->id }}" aria-describedby="service-{{ $service->id }}-description" name="services[]" value="{{ $service->id }}" type="checkbox"
                                class="h-4 w-4 rounded border-line text-primary-600 focus:ring-primary-600">
                        </div>
                        <div class="ml-3 text-sm leading-6 flex-1 flex items-center justify-between">
                            <div>
                                <label for="service-{{ $service->id }}" class="font-medium text-text-main">{{ $service->name }}</label>
                                <span id="service-{{ $service->id }}-description" class="text-text-secondary mx-2">- {{ $service->description }} (Default: {{ $service->due_day }}th {{ $service->frequency }})</span>
                            </div>
                            <!-- Custom Day Input -->
                            <div class="flex items-center gap-2">
                                <label for="custom-day-{{ $service->id }}" class="text-xs text-text-secondary">Custom Day:</label>
                                <input type="number" min="1" max="31"
                                    name="custom_due_days[{{ $service->id }}]"
                                    id="custom-day-{{ $service->id }}"
                                    class="w-16 rounded-md border-0 py-1 text-text-main shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-xs sm:leading-6"
                                    placeholder="Day">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex justify-between border-t pt-6">
                <button type="button" onclick="switchTab('engagement')" class="text-sm font-semibold text-text-main">Back</button>
                <button type="submit" class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">Create Client</button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabsContainer = document.getElementById('formTabs');

        if (tabsContainer) {
            tabsContainer.addEventListener('click', function(e) {
                // Handle clicks on buttons or elements inside buttons
                const btn = e.target.closest('.tab-btn');

                if (!btn) return;

                // Get tab name from ID (tab-basic -> basic)
                const tabName = btn.id.replace('tab-', '');

                // Reset all tabs
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('border-primary-500', 'text-primary-600');
                    b.classList.add('border-transparent', 'text-text-secondary');
                });

                document.querySelectorAll('.tab-panel').forEach(panel => {
                    panel.classList.add('hidden');
                });

                // Activate selected
                btn.classList.remove('border-transparent', 'text-text-secondary');
                btn.classList.add('border-primary-500', 'text-primary-600');

                const panel = document.getElementById('panel-' + tabName);
                if (panel) {
                    panel.classList.remove('hidden');
                }
            });
        }
    });
</script>
@endsection

@section('scripts')
<script>
    function switchTab(tabName) {
        // Hide all panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.add('hidden');
        });

        // Show selected panel
        document.getElementById('panel-' + tabName).classList.remove('hidden');

        // Reset all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-primary-500', 'text-primary-600');
            btn.classList.add('border-transparent', 'text-text-secondary');
        });

        // Highlight selected button
        const activeBtn = document.getElementById('tab-' + tabName);
        activeBtn.classList.remove('border-transparent', 'text-text-secondary');
        activeBtn.classList.add('border-primary-500', 'text-primary-600');
    }

    // Initialize first tab
    document.addEventListener('DOMContentLoaded', function() {
        switchTab('basic');
    });
</script>
@endsection