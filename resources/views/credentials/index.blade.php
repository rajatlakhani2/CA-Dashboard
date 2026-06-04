@extends('layouts.app')

@section('header')
Client Passwords & Credentials
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between gap-4">
        <!-- Search & Filter -->
        <form method="GET" action="{{ route('credentials.index') }}" class="flex flex-1 flex-wrap gap-4">
            <select name="category" class="rounded-md border-line py-2 text-sm" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
            <div class="relative flex-1 min-w-[200px] max-w-lg">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by client name, reference, portal, or username..."
                    class="block w-full rounded-md border-0 py-2 pl-10 ring-1 ring-inset ring-line placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
            </div>
            <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Search</button>
            @if(request('search'))
            <a href="{{ route('credentials.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-500 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Clear</a>
            @endif
        </form>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-lg bg-bg-card shadow">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-bg-body">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-text-main sm:pl-6">Client</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Category</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Portal / Service</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Last accessed</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Username / User ID</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Password</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Notes</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line bg-bg-card">
                @forelse($credentials as $cred)
                <tr class="hover:bg-gray-50">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                        <div class="font-medium text-text-main"><a href="{{ route('clients.edit', $cred->client_id) }}" class="text-primary-600 hover:underline">{{ $cred->client?->name ?? 'Unknown Client' }}</a></div>
                        <div class="text-xs text-gray-500">{{ $cred->client?->group_name ?? $cred->client?->client_code ?? '-' }}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $cred->category ?? 'Other' }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-text-main font-medium">
                        {{ $cred->portal_name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-xs text-text-secondary">
                        @if($cred->last_accessed_at)
                        {{ $cred->last_accessed_at->format('d M Y') }}
                        <span class="block text-gray-400">{{ $cred->lastAccessedBy?->name ?? '—' }}</span>
                        @else
                        <span class="text-gray-400">Never</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-gray-800">
                        {{ $cred->username ?: '-' }}
                        @if($cred->username)
                        <button type="button"
                            onclick="credentialVaultCopy(this, '{{ route('credentials.audit', $cred) }}', 'copied_username')"
                            data-copy-value="{{ e($cred->username) }}"
                            class="ml-2 text-gray-400 hover:text-indigo-600 inline" title="Copy User ID">
                            <svg class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-gray-800">
                        <div class="flex items-center">
                            @include('credentials.partials.vault-password-field', [
                                'credential' => $cred,
                                'inputId' => 'pwd-global-' . $cred->id,
                            ])
                        </div>
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $cred->notes }}">
                        {{ $cred->notes ?: '-' }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <form action="{{ route('credentials.destroy', $cred) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this password?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-4">
                        @include('partials.empty-state', [
                            'title' => request('search') || request('category') ? 'No credentials match' : 'Credential vault is empty',
                            'description' => 'Store portal logins from a client profile (Edit → Passwords) or after onboarding.',
                            'icon' => 'credential',
                            'actionLabel' => 'View clients',
                            'actionUrl' => route('clients.index'),
                        ])
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {!! $credentials->links() !!}
    </div>
</div>

@include('credentials.partials.vault-audit-script')
@endsection
