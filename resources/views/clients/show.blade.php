@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <div class="flex items-center space-x-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
            {{ $client->client_code }}
        </span>
        @include('partials.status-badge', [
            'status' => $client->status,
            'type' => match ($client->status) {
                'On-Hold' => 'warning',
                'Closed' => 'danger',
                default => 'neutral',
            },
        ])
    </div>
    <div class="flex flex-wrap items-center gap-3">
        @include('clients.partials.whatsapp-quick-actions', ['client' => $client])
        <div class="flex rounded-md shadow-sm">
            @if(auth()->user()?->hasRole('partner', 'manager'))
            <a href="{{ route('ledger.show', $client) }}" class="relative inline-flex items-center rounded-l-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10">
                Client Ledger
            </a>
            @endif
            <a href="{{ route('onboarding.show', $client) }}" class="relative -ml-px inline-flex items-center bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 {{ auth()->user()?->hasRole('partner', 'manager') ? '' : 'rounded-l-md' }}">
                Onboarding
            </a>
            @if(auth()->user()?->hasRole('partner', 'manager'))
            <a href="{{ route('dscs.index', ['search' => $client->name]) }}" class="relative -ml-px inline-flex items-center rounded-r-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10">
                DSCs
            </a>
            @endif
        </div>
        @can('create', App\Models\Invoice::class)
        <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-md text-sm font-semibold shadow-sm">
            + New Invoice
        </a>
        @endcan
        <a href="{{ route('clients.edit', $client) }}" class="bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 px-3 py-2 rounded-md text-sm font-semibold shadow-sm">
            Edit Profile
        </a>
        @if(auth()->user()?->managesFirmModules())
        <form method="POST" action="{{ route('clients.portal-link', $client) }}" class="inline">
            @csrf
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-md text-sm font-semibold shadow-sm">Portal link</button>
        </form>
        @endif
    </div>
</div>
@endsection

@section('content')
@php
    $canManageFirm = auth()->user()?->managesFirmModules();
    $canViewClientFinance = $canManageFirm || auth()->user()?->isAssociate();
@endphp
<div class="space-y-6" x-data="{ tab: '{{ request('tab', 'work') }}' }">
    @if(session('portal_url'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm">
        <p class="font-semibold text-emerald-900">Client portal link (copy now — shown once)</p>
        <input type="text" readonly value="{{ session('portal_url') }}" class="mt-2 w-full text-xs font-mono bg-white border border-emerald-200 rounded px-2 py-1" onclick="this.select()">
    </div>
    @endif

    @include('clients.partials.health-score', ['clientHealth' => $clientHealth])

    <!-- Summary strip -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm divide-y divide-slate-100 sm:divide-y-0 sm:divide-x sm:flex">
        <div class="px-4 py-3 sm:flex-1 min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">PAN</p>
            <p class="mt-0.5 text-sm font-semibold text-slate-900 truncate">{{ $client->pan }}</p>
        </div>
        <div class="px-4 py-3 sm:flex-1 min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Manager</p>
            <p class="mt-0.5 text-sm font-semibold text-slate-900 truncate">{{ $client->manager->name ?? 'Unassigned' }}</p>
        </div>
        @if($canViewClientFinance)
        <div class="px-4 py-3 sm:flex-1 min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Outstanding</p>
            @if($canManageFirm)
            <a href="{{ route('ledger.show', $client) }}" class="mt-0.5 block text-sm font-semibold text-money-negative hover:underline">₹ {{ number_format($totalOutstanding, 2) }}</a>
            @else
            <p class="mt-0.5 text-sm font-semibold text-money-negative">₹ {{ number_format($totalOutstanding, 2) }}</p>
            @endif
        </div>
        @endif
        <div class="px-4 py-3 sm:flex-1 min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Next due</p>
            @if($nextDue)
            <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ $nextDue->clientService->service->name ?? 'Compliance' }}</p>
            <p class="text-xs text-slate-500">{{ $nextDue->due_date->format('d M Y') }}
                @include('partials.status-badge', ['status' => $nextDue->status, 'class' => 'ml-1'])
            </p>
            @else
            <p class="mt-0.5 text-sm text-slate-500">None pending</p>
            @endif
        </div>
        @if($canViewClientFinance)
        <div class="px-4 py-3 sm:flex-1 min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last invoice</p>
            @if($lastInvoice)
            <a href="{{ route('invoices.show', $lastInvoice) }}" class="mt-0.5 block text-sm font-semibold text-slate-900 hover:text-indigo-600 truncate">{{ $lastInvoice->invoice_number }}</a>
            <p class="text-xs text-slate-500">₹ {{ number_format($lastInvoice->total_amount, 2) }}
                @include('partials.status-badge', ['status' => $lastInvoice->status, 'class' => 'ml-1'])
            </p>
            @else
            <p class="mt-0.5 text-sm text-slate-500">No invoices yet</p>
            @endif
        </div>
        @endif
        <div class="px-4 py-3 sm:flex-1 min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active tasks</p>
            <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ $activeTasks->count() }}</p>
        </div>
    </div>

    @if(auth()->user()?->managesFirmModules())
    <div class="mb-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div>@include('clients.partials.ai-assistant')</div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-2">Upload for OCR review</h3>
            <p class="text-xs text-slate-500 mb-3">Queued for partner review (filename hints until OCR API is added).</p>
            <form action="{{ route('document-ingestions.store', $client) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                @csrf
                <input type="file" name="document" required class="block w-full text-xs">
                <input type="text" name="document_type" placeholder="Type (e.g. GSTR notice)" class="w-full rounded-md border-slate-300 text-sm">
                <button type="submit" class="w-full py-2 bg-slate-800 text-white text-xs font-semibold rounded-lg">Send to review queue</button>
            </form>
            <a href="{{ route('document-ingestions.index') }}" class="text-xs text-indigo-600 font-semibold mt-2 inline-block">Open review queue →</a>
        </div>
    </div>
    @endif

    @if(isset($complianceRisks) && $complianceRisks->isNotEmpty())
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
        <p class="font-semibold text-amber-900">Compliance risk</p>
        <ul class="mt-1 space-y-1 text-amber-800 text-xs">
            @foreach($complianceRisks as $risk)
            <li>
                <strong>{{ $risk->service?->name ?? 'Service' }}</strong> — {{ $risk->level }} ({{ $risk->score }}/100)
                @if($risk->signals) · {{ implode('; ', $risk->signals) }} @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Tabs -->
    <div class="border-b border-slate-200 bg-white rounded-t-xl shadow-sm">
        <nav class="flex overflow-x-auto -mb-px px-1" aria-label="Client sections">
            <button type="button" @click="tab = 'work'" :class="tab === 'work' ? 'client-tab active' : 'client-tab'">Work</button>
            @if($canViewClientFinance)
            <button type="button" @click="tab = 'finance'" :class="tab === 'finance' ? 'client-tab active' : 'client-tab'">Finance</button>
            @endif
            <button type="button" @click="tab = 'timeline'" :class="tab === 'timeline' ? 'client-tab active' : 'client-tab'">Timeline</button>
            <button type="button" @click="tab = 'profile'" :class="tab === 'profile' ? 'client-tab active' : 'client-tab'">Profile</button>
        </nav>
    </div>

    <div x-show="tab === 'work'" x-cloak class="space-y-6">

            @if(auth()->user()?->managesFirmModules() || auth()->user()?->canAccessModule('tasks'))
            @if(isset($documentChecklists) && $documentChecklists->isNotEmpty())
            <div id="document-checklists" class="bg-white shadow sm:rounded-lg overflow-hidden p-4 border border-amber-100">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Document checklists</h3>
                <p class="text-sm text-gray-500 mb-4">Mark documents received per opted service before filing.</p>
                <div class="space-y-4">
                    @foreach($documentChecklists as $checklist)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <h4 class="font-semibold text-slate-900">{{ $checklist['service_name'] }}</h4>
                            @if($checklist['missing'] > 0)
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-800 ring-1 ring-amber-600/20">
                                {{ $checklist['missing'] }} missing
                            </span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-bold text-green-700 ring-1 ring-green-600/20">
                                All received
                            </span>
                            @endif
                        </div>
                        <ul class="space-y-2">
                            @foreach($checklist['items'] as $item)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="{{ $item['is_received'] ? 'text-slate-500 line-through' : 'text-slate-800 font-medium' }}">{{ $item['name'] }}</span>
                                <form method="POST" action="{{ route('clients.service-documents.toggle', [$client, $checklist['client_service_id'], $item['requirement_id']]) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="received" value="{{ $item['is_received'] ? '0' : '1' }}">
                                    <button type="submit" class="text-xs font-bold {{ $item['is_received'] ? 'text-slate-400 hover:text-slate-600' : 'text-indigo-600 hover:text-indigo-800' }}">
                                        {{ $item['is_received'] ? 'Undo' : 'Mark received' }}
                                    </button>
                                </form>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden p-4">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Service checklists</h3>
                <p class="text-sm text-gray-500 mb-3">Spawn task templates for this client (e.g. ITR filing steps).</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($client->optedServices as $service)
                        @if($service->taskTemplates->isNotEmpty())
                        <form action="{{ route('services.spawn-tasks', [$service, $client]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg hover:bg-indigo-100 border border-indigo-200">
                                {{ $service->name }} ({{ $service->taskTemplates->count() }} tasks)
                            </button>
                        </form>
                        @endif
                    @endforeach
                    @if($client->optedServices->filter(fn ($s) => $s->taskTemplates->isNotEmpty())->isEmpty())
                    <span class="text-sm text-gray-400">Add templates under Service Master for assigned services.</span>
                    @endif
                </div>
            </div>
            @endif

            <!-- Pending Compliance -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Pending Compliance</h3>
                    <span class="text-xs font-semibold text-slate-600">{{ $serviceDues->count() }} due</span>
                </div>
                <ul role="list" class="divide-y divide-gray-200">
                    @forelse($serviceDues as $due)
                    <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 transition duration-150 ease-in-out">
                        <div class="flex items-center justify-between">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $due->clientService->service->name ?? 'Service' }}</p>
                            <div class="ml-2 flex flex-shrink-0 items-center gap-2">
                                <span class="text-xs text-slate-500">{{ $due->due_date->format('d M') }}</span>
                                @include('partials.status-badge', ['status' => $due->status])
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="px-4 py-4 text-sm text-gray-500 text-center">No pending dues. Good job!</li>
                    @endforelse
                </ul>
            </div>

            <!-- Active Tasks -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Active Work items</h3>
                </div>
                <ul role="list" class="divide-y divide-gray-200">
                    @forelse($activeTasks as $task)
                    <li class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <p class="text-sm font-medium text-gray-900">{{ $task->title }}</p>
                                <p class="text-xs text-gray-500 mt-1">Assigned to: {{ $task->assignee->name ?? 'Unassigned' }}</p>
                            </div>
                            @include('partials.status-badge', ['status' => $task->status])
                        </div>
                    </li>
                    @empty
                    <li class="px-4 py-4 text-sm text-gray-500 text-center">No active tasks.</li>
                    @endforelse
                </ul>
            </div>

            <!-- Office Notes -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Office Notes</h3>
                    <button onclick="document.getElementById('editNotesForm').classList.toggle('hidden'); document.getElementById('notesDisplay').classList.toggle('hidden');" class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                </div>
                <div class="p-4 text-sm text-gray-700">
                    <div id="notesDisplay" class="whitespace-pre-wrap">{{ $client->office_notes ?? 'No office notes added.' }}</div>
                    <form id="editNotesForm" action="{{ route('clients.update', $client) }}" method="POST" class="hidden">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $client->name }}">
                        <input type="hidden" name="pan" value="{{ $client->pan }}">
                        <input type="hidden" name="category" value="{{ $client->category }}">
                        <input type="hidden" name="status" value="{{ $client->status }}">
                        <textarea name="office_notes" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $client->office_notes }}</textarea>
                        <div class="mt-2 flex justify-end gap-2">
                             <button type="button" onclick="document.getElementById('editNotesForm').classList.add('hidden'); document.getElementById('notesDisplay').classList.remove('hidden');" class="bg-white py-1 px-2 border border-gray-300 rounded-md text-xs shadow-sm hover:bg-gray-50">Cancel</button>
                             <button type="submit" class="bg-indigo-600 text-white py-1 px-2 border border-transparent rounded-md text-xs shadow-sm hover:bg-indigo-700">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Client Worksheet -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Client Worksheet (Unbilled Items)</h3>
                    @if(auth()->user()?->hasRole('partner', 'manager'))
                    <a href="{{ route('billing.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-900">Go to Billing Queue &rarr;</a>
                    @endif
                </div>
                <div class="p-4 border-b border-gray-200">
                    <form action="{{ route('clients.worksheets.store', $client) }}" method="POST" class="flex gap-2 items-end">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-700">Description</label>
                            <input type="text" name="description" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                        </div>
                        <div class="w-32">
                            <label class="block text-xs font-medium text-gray-700">Amount (₹)</label>
                            <input type="number" step="0.01" name="amount" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                        </div>
                        <div class="w-32">
                            <label class="block text-xs font-medium text-gray-700">Date</label>
                            <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white py-2 px-3 border border-transparent rounded-md text-xs shadow-sm hover:bg-indigo-700 font-medium">Add</button>
                    </form>
                </div>
                <ul role="list" class="divide-y divide-gray-200">
                    @forelse($unbilledWorksheets as $item)
                    <li class="px-4 py-3 sm:px-6 flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $item->description }}</p>
                            <p class="text-xs text-gray-500">{{ $item->date->format('d M, Y') }} &bull; Added by {{ $item->creator->name ?? 'System' }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-bold text-gray-900">₹ {{ number_format($item->amount, 2) }}</span>
                            <form action="{{ route('clients.worksheets.destroy', [$client, $item]) }}" method="POST" onsubmit="return confirm('Delete this worksheet item?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-medium">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </li>
                    @empty
                    <li class="px-4 py-4 text-sm text-gray-500 text-center">No unbilled worksheet items.</li>
                    @endforelse
                </ul>
            </div>

    </div>

    <div x-show="tab === 'timeline'" x-cloak class="space-y-4">
        @include('clients.partials.timeline', ['timeline' => $timeline ?? collect()])
    </div>

    @if($canViewClientFinance)
    <div x-show="tab === 'finance'" x-cloak class="space-y-6">
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-slate-100">
                <dt class="truncate text-sm font-medium text-slate-500">Total billed</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">₹ {{ number_format($totalBilled, 2) }}</dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-slate-100">
                <dt class="truncate text-sm font-medium text-slate-500">Collected</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-money-positive">₹ {{ number_format($totalCollected, 2) }}</dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-slate-100">
                <dt class="truncate text-sm font-medium text-slate-500">Outstanding</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-money-negative">₹ {{ number_format($totalOutstanding, 2) }}</dd>
            </div>
        </dl>

        @if(auth()->user()?->canViewPortfolioInvoices())
        <div class="bg-white shadow sm:rounded-lg overflow-hidden border border-slate-100">
            <div class="px-4 py-4 border-b border-gray-200 bg-gray-50 flex justify-between">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Recent Invoices</h3>
                <a href="{{ route('invoices.index', ['client_id' => $client->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($client->invoices as $invoice)
                <li class="px-4 py-3">
                    <div class="flex justify-between items-center">
                        <div class="text-sm">
                            <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                                {{ $invoice->invoice_number }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $invoice->date->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-900">₹ {{ number_format($invoice->total_amount, 2) }}</p>
                            @include('partials.status-badge', ['status' => $invoice->status])
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-4 py-6 text-sm text-slate-500 text-center">No invoices yet.</li>
                @endforelse
            </ul>
        </div>
        @endif
    </div>
    @endif

    <div x-show="tab === 'profile'" x-cloak class="space-y-6 max-w-3xl">
            <div class="bg-white shadow sm:rounded-lg border border-slate-100">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Profile</h3>
                </div>
                <div class="px-4 py-4 text-sm">
                    <div class="mt-2 text-gray-900 font-medium">Primary Contact</div>
                    <div class="text-gray-500">{{ $client->primary_contact_name ?? 'N/A' }}</div>

                    <div class="mt-3 text-gray-900 font-medium">Email</div>
                    <div class="text-gray-500">{{ $client->primary_contact_email ?? 'N/A' }}</div>

                    <div class="mt-3 text-gray-900 font-medium">Phone</div>
                    <div class="text-gray-500">{{ $client->primary_contact_phone ?? 'N/A' }}</div>

                    <div class="mt-3 text-gray-900 font-medium">PAN / GSTIN</div>
                    <div class="text-gray-500">{{ $client->pan }} / {{ $client->gstin }}</div>

                    <div class="mt-3 text-gray-900 font-medium">Opted Services</div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($client->optedServices as $service)
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                            {{ $service->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>

    </div>
</div>
@endsection