@extends('layouts.app')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('clients.index') }}" class="text-gray-400 hover:text-gray-500">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
    </a>
    <div class="flex items-baseline gap-2">
        <span>Edit Client: {{ $client->name }}</span>
        @if($client->group_name)
        <span class="text-sm text-gray-400 font-normal">({{ $client->group_name }})</span>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg">
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
            <button type="button" onclick="switchTab('personal')" class="tab-btn border-transparent text-text-secondary hover:border-line hover:text-text-main whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" id="tab-personal">
                Personal Reminders
            </button>
            <button type="button" onclick="switchTab('credentials')" class="tab-btn border-transparent text-text-secondary hover:border-line hover:text-text-main whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" id="tab-credentials">
                Passwords & Credentials
            </button>
        </nav>
    </div>

    <form action="{{ route('clients.update', $client) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')

        <!-- Tab 1: Basic Info -->
        <div id="panel-basic" class="tab-panel space-y-6">
            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <!-- Name -->
                <div class="sm:col-span-3">
                    <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Client Name *</label>
                    <div class="mt-2">
                        <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- Group Name / Reference -->
                <div class="sm:col-span-3">
                    <label for="group_name" class="block text-sm font-medium leading-6 text-gray-900">Group Name / Reference</label>
                    <div class="mt-2">
                        <input type="text" name="group_name" id="group_name" value="{{ old('group_name', $client->group_name) }}" placeholder="e.g. Nilesh Bhai, Boarda" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- Entity Type -->
                <div class="sm:col-span-3">
                    <label for="entity_type" class="block text-sm font-medium leading-6 text-gray-900">Entity Type</label>
                    <div class="mt-2">
                        <select name="entity_type" id="entity_type" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            @foreach(['Individual', 'Proprietorship', 'Partnership', 'LLP', 'Private Limited', 'Public Limited'] as $type)
                            <option value="{{ $type }}" {{ old('entity_type', $client->entity_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- PAN -->
                <div class="sm:col-span-3">
                    <label for="pan" class="block text-sm font-medium leading-6 text-gray-900">PAN Number *</label>
                    <div class="mt-2">
                        <input type="text" name="pan" id="pan" value="{{ old('pan', $client->pan) }}" required class="uppercase block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- GSTIN -->
                <div class="sm:col-span-3">
                    <label for="gstin" class="block text-sm font-medium leading-6 text-gray-900">GSTIN</label>
                    <div class="mt-2">
                        <input type="text" name="gstin" id="gstin" value="{{ old('gstin', $client->gstin) }}" class="uppercase block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- Industry -->
                <div class="sm:col-span-3">
                    <label for="industry" class="block text-sm font-medium leading-6 text-gray-900">Industry</label>
                    <div class="mt-2">
                        <input type="text" name="industry" id="industry" value="{{ old('industry', $client->industry) }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- Status -->
                <div class="sm:col-span-3">
                    <label for="status" class="block text-sm font-medium leading-6 text-gray-900">Status *</label>
                    <div class="mt-2">
                        <select name="status" id="status" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            @foreach(['Active', 'On-Hold', 'Closed'] as $status)
                            <option value="{{ $status }}" {{ old('status', $client->status) == $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Tags -->
                <div class="sm:col-span-6">
                    <label for="tags" class="block text-sm font-medium leading-6 text-gray-900">Tags (comma separated)</label>
                    <div class="mt-2">
                        <input type="text" name="tags" id="tags" value="{{ old('tags', is_array($client->tags) ? implode(', ', $client->tags) : $client->tags) }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <!-- Office Notes -->
                <div class="sm:col-span-6">
                    <label for="office_notes" class="block text-sm font-medium leading-6 text-gray-900">Office Notes (Internal use only)</label>
                    <div class="mt-2">
                        <textarea name="office_notes" id="office_notes" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">{{ old('office_notes', $client->office_notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t pt-6">
                <a href="{{ route('clients.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Close</a>
                <button type="submit" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Save</button>
                <button type="button" onclick="switchTab('contact')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Next: Contact Info</button>
            </div>
        </div>

        <!-- Tab 2: Contact Info -->
        <div id="panel-contact" class="tab-panel hidden space-y-6">
            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <!-- Primary Contact -->
                <div class="sm:col-span-6">
                    <h3 class="text-base font-semibold leading-7 text-gray-900">Primary Contact Person</h3>
                </div>

                <div class="sm:col-span-2">
                    <label for="primary_contact_name" class="block text-sm font-medium leading-6 text-gray-900">Name</label>
                    <div class="mt-2">
                        <input type="text" name="primary_contact_name" id="primary_contact_name" value="{{ old('primary_contact_name', $client->primary_contact_name) }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="primary_contact_phone" class="block text-sm font-medium leading-6 text-gray-900">Phone</label>
                    <div class="mt-2">
                        <input type="text" name="primary_contact_phone" id="primary_contact_phone" value="{{ old('primary_contact_phone', $client->primary_contact_phone) }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="primary_contact_email" class="block text-sm font-medium leading-6 text-gray-900">Email</label>
                    <div class="mt-2">
                        <input type="email" name="primary_contact_email" id="primary_contact_email" value="{{ old('primary_contact_email', $client->primary_contact_email) }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div class="sm:col-span-6 border-t pt-4">
                    <h3 class="text-base font-semibold leading-7 text-gray-900">Address Details</h3>
                </div>

                <div class="sm:col-span-3">
                    <label for="registered_address" class="block text-sm font-medium leading-6 text-gray-900">Registered Address</label>
                    <div class="mt-2">
                        <textarea name="registered_address" id="registered_address" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">{{ old('registered_address', $client->registered_address) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center border-t pt-6">
                <button type="button" onclick="switchTab('basic')" class="text-sm font-semibold text-gray-900">Back</button>
                <div class="flex gap-3">
                    <a href="{{ route('clients.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Close</a>
                    <button type="submit" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Save</button>
                    <button type="button" onclick="switchTab('engagement')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Next: Engagement</button>
                </div>
            </div>
        </div>

        <!-- Tab 3: Engagement -->
        <div id="panel-engagement" class="tab-panel hidden space-y-6">
            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <!-- Category -->
                <div class="sm:col-span-2">
                    <label for="category" class="block text-sm font-medium leading-6 text-gray-900">Client Category *</label>
                    <div class="mt-2">
                        <select name="category" id="category" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            @foreach(['A' => 'Category A (Premium)', 'B' => 'Category B (Standard)', 'C' => 'Category C (Basic)'] as $val => $label)
                            <option value="{{ $val }}" {{ old('category', $client->category) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Billing Cycle -->
                <div class="sm:col-span-2">
                    <label for="billing_cycle" class="block text-sm font-medium leading-6 text-gray-900">Billing Cycle</label>
                    <div class="mt-2">
                        <select name="billing_cycle" id="billing_cycle" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            @foreach(['Monthly', 'Quarterly', 'Annual'] as $cycle)
                            <option value="{{ $cycle }}" {{ old('billing_cycle', $client->billing_cycle) == $cycle ? 'selected' : '' }}>{{ $cycle }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center border-t pt-6">
                <button type="button" onclick="switchTab('contact')" class="text-sm font-semibold text-gray-900">Back</button>
                <div class="flex gap-3">
                    <a href="{{ route('clients.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Close</a>
                    <button type="submit" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Save</button>
                    <button type="button" onclick="switchTab('services')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Next: Services</button>
                </div>
            </div>
        </div>

        <!-- Tab 4: Services -->
        <div id="panel-services" class="tab-panel hidden space-y-6">
            <div class="border-b border-gray-200 pb-4">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Opted Services</h3>
                <p class="mt-2 text-sm text-gray-500">Select the recurring compliance services for this client. The system will auto-generate reminders based on the frequency.</p>
            </div>

            <fieldset>
                <div class="space-y-4">
                    @foreach($services as $service)
                    @php
                    $isOpted = $optedServices->has($service->id);
                    $customDay = $isOpted ? $optedServices[$service->id]->pivot->custom_due_day : '';
                    @endphp
                    <div class="relative flex items-center py-2">
                        <div class="flex h-6 items-center">
                            <input id="service-{{ $service->id }}" aria-describedby="service-{{ $service->id }}-description" name="services[]" value="{{ $service->id }}" type="checkbox"
                                {{ $isOpted ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600">
                        </div>
                        <div class="ml-3 text-sm leading-6 flex-1 flex items-center justify-between">
                            <div>
                                <label for="service-{{ $service->id }}" class="font-medium text-gray-900">{{ $service->name }}</label>
                                <span id="service-{{ $service->id }}-description" class="text-gray-500 mx-2">- {{ $service->description }} (Default: {{ $service->due_day }}th {{ $service->frequency }})</span>
                            </div>
                            <!-- Custom Day Input -->
                            <div class="flex items-center gap-2">
                                <label for="custom-day-{{ $service->id }}" class="text-xs text-gray-500">Custom Day:</label>
                                <input type="number" min="1" max="31"
                                    name="custom_due_days[{{ $service->id }}]"
                                    id="custom-day-{{ $service->id }}"
                                    value="{{ $customDay }}"
                                    class="w-16 rounded-md border-0 py-1 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-xs sm:leading-6"
                                    placeholder="Day">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex justify-between items-center border-t pt-6">
                <button type="button" onclick="switchTab('engagement')" class="text-sm font-semibold text-gray-900">Back</button>
                <div class="flex gap-3">
                    <a href="{{ route('clients.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Close</a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Update Client</button>
                </div>
            </div>
        </div>
    </form>

    <!-- Tab 5: Personal Reminders (Outside Main Form to avoid nesting forms if we use modals or separate forms) -->
    <div id="panel-personal" class="tab-panel hidden p-6 space-y-6">
        <div class="rounded-2xl bg-gradient-to-r from-teal-600 to-emerald-600 p-6 text-white flex flex-wrap justify-between items-center gap-4 shadow-lg">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-100">Client renewals</p>
                <h3 class="text-lg font-black mt-1">Personal reminders for {{ $client->name }}</h3>
                <p class="text-sm text-emerald-50 mt-1">LIC, medical, loans — linked to this client profile.</p>
            </div>
            <div class="text-center bg-white/20 rounded-xl px-4 py-2">
                <div class="text-2xl font-black">{{ $client->personalRenewals->count() }}</div>
                <div class="text-[10px] uppercase font-bold">Records</div>
            </div>
        </div>
        <div class="flex justify-between items-center border-b border-gray-200 pb-4">
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-900">Renewal list</h3>
            </div>
            <button onclick="document.getElementById('addRenewalModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow text-sm">
                + Add Renewal/Doc
            </button>
        </div>

        <!-- Renewal List -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($client->personalRenewals as $renewal)
                <li class="px-4 py-4 sm:px-6 hover:bg-emerald-50/40 transition border-l-4 border-transparent hover:border-emerald-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                @if($renewal->document_path)
                                <a href="{{ asset('storage/' . $renewal->document_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900" title="View Document">
                                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                    </svg>
                                </a>
                                @else
                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                @endif
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-bold text-indigo-600">{{ $renewal->title }}</h4>
                                <div class="flex items-center text-sm text-gray-500 mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 mr-2">
                                        {{ $renewal->category }}
                                    </span>
                                    <span class="mr-2">Due: {{ $renewal->due_date->format('d M Y') }}</span>
                                    @if($renewal->financial_year)
                                    <span class="mr-2 text-xs text-gray-400">FY: {{ $renewal->financial_year }}</span>
                                    @endif
                                    @if($renewal->version)
                                    <span class="mr-2 text-xs text-gray-400">v{{ $renewal->version }}</span>
                                    @endif
                                    <span class="font-medium text-gray-900">₹ {{ number_format($renewal->amount) }}</span>
                                </div>
                                @if($renewal->expiry_date)
                                <p class="text-[10px] text-red-500 font-bold mt-1">EXPIRES: {{ $renewal->expiry_date->format('d M, Y') }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <form action="{{ route('personal-renewals.destroy', $renewal) }}" method="POST" onsubmit="return confirm('Delete this renewal?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-gray-500 italic">No personal reminders added yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Add Renewal Modal -->
    <div id="addRenewalModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('addRenewalModal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('personal-renewals.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $client->id }}">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add Personal Reminder</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title (e.g. LIC Policy 123)</label>
                                <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Category</label>
                                    <select name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="LIC">LIC</option>
                                        <option value="Medical">Medical</option>
                                        <option value="Loan">Loan</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                                    <input type="number" name="amount" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Financial Year</label>
                                    <input type="text" name="financial_year" placeholder="e.g. 2024-25" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Version / Rev</label>
                                    <input type="text" name="version" placeholder="e.g. 1.0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Due Date</label>
                                    <input type="date" name="due_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Frequency</label>
                                <select name="frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">One-Time</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Quarterly">Quarterly</option>
                                    <option value="Half-Yearly">Half-Yearly</option>
                                    <option value="Yearly">Yearly</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Attachment (PDF/Image)</label>
                            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                <button type="button" onclick="document.getElementById('addRenewalModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
            </div>
            </form>
        </div>
    </div>
</div>
    <!-- Tab 6: Credentials -->
    <div id="panel-credentials" class="tab-panel hidden p-6 space-y-6">
        <div class="flex justify-between items-center border-b border-gray-200 pb-4">
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-900">Passwords & Credentials</h3>
                <p class="mt-2 text-sm text-gray-500">Store and manage portal logins like Income Tax, GST, TAN, MCA etc. securely.</p>
            </div>
            <button onclick="document.getElementById('addCredentialModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow text-sm">
                + Add Password
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($client->credentials as $credential)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-bold text-gray-900">{{ $credential->portal_name }}</h4>
                    <form action="{{ route('credentials.destroy', $credential) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this password?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                </div>
                <div class="space-y-2 mt-3">
                    <div>
                        <span class="text-xs text-gray-500 block">User ID / Username</span>
                        <div class="flex items-center justify-between bg-gray-50 px-2 py-1 rounded">
                            <span class="text-sm font-medium font-mono text-gray-800">{{ $credential->username ?: '-' }}</span>
                            @if($credential->username)
                            <button type="button"
                                onclick="credentialVaultCopy(this, '{{ route('credentials.audit', $credential) }}', 'copied_username')"
                                data-copy-value="{{ $credential->username }}"
                                class="text-gray-400 hover:text-indigo-600 p-1" title="Copy User ID">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Password</span>
                        <div class="flex items-center justify-between bg-gray-50 px-2 py-1 rounded">
                            <input type="password" readonly value="{{ $credential->password }}" class="bg-transparent border-none text-sm font-medium font-mono text-gray-800 p-0 w-full focus:ring-0" id="pwd-{{ $credential->id }}">
                            <div class="flex gap-1 ml-2">
                                <button type="button"
                                    onclick="credentialVaultTogglePassword(this, '{{ route('credentials.audit', $credential) }}')"
                                    data-target="pwd-{{ $credential->id }}"
                                    class="text-gray-400 hover:text-indigo-600 p-1" title="Toggle Visibility">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button type="button"
                                    onclick="credentialVaultCopy(this, '{{ route('credentials.audit', $credential) }}', 'copied_password')"
                                    data-copy-value="{{ $credential->password }}"
                                    class="text-gray-400 hover:text-indigo-600 p-1" title="Copy Password">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @if($credential->notes)
                <p class="mt-2 text-xs text-gray-500 italic">{{ $credential->notes }}</p>
                @endif
            </div>
            @empty
            <div class="col-span-full py-8 text-center text-gray-500 italic">No passwords saved for this client yet.</div>
            @endforelse
        </div>
    </div>

    <!-- Add Credential Modal -->
    <div id="addCredentialModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('addCredentialModal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form action="{{ route('credentials.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $client->id }}">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add Password/Credential</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category</label>
                                <select name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach(\App\Models\ClientCredential::CATEGORIES as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Portal / Service Name</label>
                                <input type="text" name="portal_name" placeholder="e.g. Income Tax, GST, PF" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">User ID / Username</label>
                                <input type="text" name="username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="text" name="password" class="mt-1 block w-full font-mono rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                        <button type="button" onclick="document.getElementById('addCredentialModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function switchTab(tabName) {
        // Hide all panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.add('hidden');
        });

        // Show selected panel
        const panel = document.getElementById('panel-' + tabName);
        if (panel) {
            panel.classList.remove('hidden');
        }

        // Reset all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-primary-500', 'text-primary-600', 'border-indigo-500', 'text-indigo-600');
            btn.classList.add('border-transparent', 'text-text-secondary');
        });

        // Highlight selected button
        const activeBtn = document.getElementById('tab-' + tabName);
        if (activeBtn) {
            activeBtn.classList.remove('border-transparent', 'text-text-secondary');
            activeBtn.classList.add('border-primary-500', 'text-primary-600');
        }

        // Save active tab to session storage to persist across reloads
        sessionStorage.setItem('activeClientTab', tabName);
    }

    // On page load, restore the active tab
    document.addEventListener('DOMContentLoaded', () => {
        let activeTab = sessionStorage.getItem('activeClientTab') || 'basic';
        
        // If there are validation errors, we can intelligently switch to the tab with errors
        @if($errors->has('portal_name') || $errors->has('username') || session('success') == 'Credential added successfully.')
            activeTab = 'credentials';
        @endif
        @if($errors->has('title') || $errors->has('category') || session('success') == 'Reminder added successfully.')
            activeTab = 'personal';
        @endif

        switchTab(activeTab);
    });
</script>

@include('credentials.partials.vault-audit-script')
@endsection