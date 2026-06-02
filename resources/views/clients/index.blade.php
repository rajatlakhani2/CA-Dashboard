@extends('layouts.app')

@section('header')
Clients
@endsection

@section('content')
<div class="space-y-6">
    @if(isset($pendingClients) && $pendingClients->count() > 0)
    <div class="rounded-lg border border-amber-300 bg-amber-50 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-amber-200 bg-amber-100/80">
            <h3 class="text-sm font-bold text-amber-900">Pending client approvals ({{ $pendingClients->count() }})</h3>
            <p class="text-xs text-amber-800 mt-0.5">Submitted by articles — approve to make visible firm-wide.</p>
        </div>
        <ul class="divide-y divide-amber-200">
            @foreach($pendingClients as $pending)
            <li class="px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold text-gray-900">{{ $pending->name }}</p>
                    <p class="text-xs text-gray-600">
                        PAN: {{ $pending->pan }}
                        @if($pending->group_name) · Group: {{ $pending->group_name }} @endif
                        · Submitted by {{ $pending->createdBy?->name ?? 'Unknown' }}
                        · {{ $pending->created_at->diffForHumans() }}
                    </p>
                </div>
                <form action="{{ route('clients.approve', $pending) }}" method="POST" onsubmit="return confirm('Approve {{ $pending->name }} for everyone?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        Approve
                    </button>
                </form>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between gap-4">
        <!-- Search & Filter -->
        <form method="GET" action="{{ route('clients.index') }}" class="flex flex-1 gap-4">
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-text-secondary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search clients by name, code, or PAN..."
                    class="block w-full rounded-md border-0 py-2 pl-10 ring-1 ring-inset ring-line placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
            </div>

            <select name="status" onchange="this.form.submit()" class="block w-40 rounded-md border-0 py-2 pl-3 pr-10 ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                <option value="">All Status</option>
                <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                <option value="On-Hold" {{ request('status') == 'On-Hold' ? 'selected' : '' }}>On-Hold</option>
                <option value="Closed" {{ request('status') == 'Closed' ? 'selected' : '' }}>Closed</option>
            </select>

            <select name="category" onchange="this.form.submit()" class="block w-40 rounded-md border-0 py-2 pl-3 pr-10 ring-1 ring-inset ring-line focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                <option value="">All Categories</option>
                <option value="A" {{ request('category') == 'A' ? 'selected' : '' }}>Category A</option>
                <option value="B" {{ request('category') == 'B' ? 'selected' : '' }}>Category B</option>
                <option value="C" {{ request('category') == 'C' ? 'selected' : '' }}>Category C</option>
            </select>
        </form>

        @can('create', App\Models\Client::class)
        <a href="{{ route('clients.create') }}" class="inline-flex items-center justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
            <svg class="-ml-0.5 mr-1.5 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" clip-rule="evenodd" />
            </svg>
            Add Client
        </a>
        @endcan
    </div>

    @can('export', App\Models\Client::class)
    <!-- Import/Export & Actions -->
    <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 mb-4 items-center">
        <!-- Download Template -->
        <a href="{{ route('clients.template') }}" class="text-sm text-primary-600 hover:text-primary-900 font-medium mr-4">
            Download Template
        </a>

        <!-- Export -->
        <a href="{{ route('clients.export') }}" class="inline-flex items-center justify-center rounded-md bg-bg-card px-3 py-2 text-sm font-semibold text-text-main shadow-sm ring-1 ring-inset ring-line hover:bg-gray-50">
            <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-text-secondary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V6.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5zm0 3a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5z" clip-rule="evenodd" />
            </svg>
            Export Excel
        </a>

        <!-- Import Form -->
        @if(auth()->user()?->isPartner())
        <a href="{{ route('clients.import.nilesh') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium mr-2">Nilesh folder preview</a>
        @endif
        <form action="{{ route('clients.import.preview') }}" method="POST" enctype="multipart/form-data" class="flex items-center space-x-2">
            @csrf
            <input type="file" name="file" class="block w-full text-sm text-slate-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-full file:border-0
                file:text-sm file:font-semibold
                file:bg-primary-50 file:text-primary-700
                hover:file:bg-primary-100
            " required>
            <button type="submit" class="inline-flex items-center rounded-md bg-bg-card px-3 py-2 text-sm font-semibold text-text-main shadow-sm ring-1 ring-inset ring-line hover:bg-gray-50">
                Preview import
            </button>
        </form>

        <!-- Bulk Delete Button -->
        <button onclick="submitBulkDelete()" class="inline-flex items-center rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-100 hover:bg-red-100">
            <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete Selected (this page)
        </button>
    </div>
    @endcan

    @if(auth()->user()?->isPartner())
    <div class="rounded-lg border border-red-200 bg-red-50/80 p-4 text-sm text-red-900">
        <p class="font-semibold">One-time delete by client reference</p>
        <p class="mt-1 text-red-800">Removes <strong>all</strong> clients with that group/reference (not only this page). Use before a fresh Nilesh import if every row shows as “update”.</p>
        <form action="{{ route('clients.purge-by-group') }}" method="POST" class="mt-3 flex flex-wrap items-end gap-3" onsubmit="return confirmPurgeByGroup(this);">
            @csrf
            @method('DELETE')
            <div>
                <label for="purge_group_name" class="block text-xs font-medium text-red-800">Client reference (group_name)</label>
                <input type="text" name="group_name" id="purge_group_name" value="Nileshbhai" required
                    class="mt-1 block w-48 rounded-md border-red-200 text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
            <div>
                <label for="purge_confirm" class="block text-xs font-medium text-red-800">Type DELETE to confirm</label>
                <input type="text" name="confirm" id="purge_confirm" placeholder="DELETE" required autocomplete="off"
                    class="mt-1 block w-32 rounded-md border-red-200 text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
            <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                Delete all with this reference
            </button>
        </form>
    </div>
    <script>
        function confirmPurgeByGroup(form) {
            const group = form.group_name.value.trim();
            const confirm = form.confirm.value.trim();
            if (confirm !== 'DELETE') {
                alert('Type DELETE in the confirmation box.');
                return false;
            }
            return confirm('Permanently delete ALL clients with reference "' + group + '"? This cannot be undone.');
        }
    </script>
    @endif

    <form id="bulkDeleteForm" action="{{ route('clients.bulk-destroy') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
        function submitBulkDelete() {
            const checkboxes = document.querySelectorAll('.client-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select clients to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete ' + checkboxes.length + ' selected clients? This action cannot be undone.')) {
                return;
            }

            const form = document.getElementById('bulkDeleteForm');
            // Clear previous inputs
            form.querySelectorAll('input[name="selected_clients[]"]').forEach(el => el.remove());

            checkboxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_clients[]';
                input.value = cb.value;
                form.appendChild(input);
            });

            form.submit();
        }
    </script>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-lg bg-bg-card shadow">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-bg-body">
                <tr>
                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text-main w-12 text-center">
                        <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-line text-primary-600 focus:ring-primary-600">
                    </th>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-text-main sm:pl-6">Client Code</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Name</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Category</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Client Reference</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Tax IDs</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Status</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line bg-bg-card">
                @forelse($clients as $client)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 text-sm text-gray-500 text-center">
                        <input type="checkbox" name="selected_clients[]" value="{{ $client->id }}" class="client-checkbox h-4 w-4 rounded border-line text-primary-600 focus:ring-primary-600">
                    </td>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-primary-600 sm:pl-6">
                        <a href="{{ route('clients.show', $client) }}">{{ $client->client_code }}</a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-text-main">
                        <div class="font-medium text-text-main">{{ $client->name }}</div>
                        <div class="text-gray-500 text-xs">
                            {{ $client->entity_type }}
                            @if($client->group_name)
                            • <span class="font-semibold">{{ $client->group_name }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                            {{ $client->category === 'A' ? 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-700/10' : '' }}
                            {{ $client->category === 'B' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10' : '' }}
                            {{ $client->category === 'C' ? 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10' : '' }}">
                            Category {{ $client->category }}
                        </span>
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-500">
                        @if($client->group_name)
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-500/10">{{ $client->group_name }}</span>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        @if($client->pan) <div>PAN: {{ $client->pan }}</div> @endif
                        @if($client->gstin) <div class="text-xs">GST: {{ $client->gstin }}</div> @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                            {{ $client->status === 'Active' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : '' }}
                            {{ $client->status === 'On-Hold' ? 'bg-yellow-50 text-yellow-800 ring-1 ring-inset ring-yellow-600/20' : '' }}
                            {{ $client->status === 'Closed' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10' : '' }}">
                            {{ $client->status }}
                        </span>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('clients.edit', $client) }}" class="text-primary-600 hover:text-primary-900 mr-3">Edit</a>
                        <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-4">
                        @include('partials.empty-state', [
                            'title' => request()->hasAny(['search', 'status', 'category', 'branch_id']) ? 'No clients match filters' : 'No clients yet',
                            'description' => request()->hasAny(['search', 'status', 'category', 'branch_id']) ? 'Try clearing filters or adjusting your search.' : 'Add your first client to start tracking compliance, billing, and work.',
                            'icon' => 'users',
                            'actionLabel' => auth()->user()?->can('create', App\Models\Client::class) ? 'Add client' : null,
                            'actionUrl' => auth()->user()?->can('create', App\Models\Client::class) ? route('clients.create') : null,
                        ])
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {!! $clients->links() !!}
    </div>
</div>
<script>
    document.getElementById('select-all').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.client-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });
</script>
@endsection