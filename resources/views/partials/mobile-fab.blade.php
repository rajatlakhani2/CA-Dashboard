@php $u = auth()->user(); @endphp
<div x-data="{ fab: false }" x-on:open-mobile-fab.window="fab = true" class="lg:hidden">
    <div x-show="fab" x-cloak @click="fab = false" class="fixed inset-0 z-[55] bg-slate-900/40"></div>
    <div x-show="fab" x-cloak x-transition
         class="fixed bottom-20 left-4 right-4 z-[56] mx-auto max-w-sm rounded-2xl bg-white border border-gray-200 shadow-2xl p-3 space-y-1">
        <p class="text-xs font-bold text-gray-500 px-2 py-1">Quick add</p>
        @can('create', App\Models\Client::class)
        <a href="{{ route('clients.create') }}" class="block rounded-xl px-3 py-3 text-sm font-semibold text-gray-900 hover:bg-indigo-50">+ Client</a>
        @endcan
        @can('create', App\Models\Task::class)
        <a href="{{ route('tasks.create') }}" class="block rounded-xl px-3 py-3 text-sm font-semibold text-gray-900 hover:bg-indigo-50">+ Task</a>
        @endcan
        @can('create', App\Models\Invoice::class)
        <a href="{{ route('invoices.create') }}" class="block rounded-xl px-3 py-3 text-sm font-semibold text-gray-900 hover:bg-indigo-50">+ Invoice</a>
        @endcan
        @if($u?->managesFirmModules() && $u?->canAccessModule('payments'))
        <a href="{{ route('payments.create') }}" class="block rounded-xl px-3 py-3 text-sm font-semibold text-gray-900 hover:bg-indigo-50">+ Payment</a>
        @endif
        <button type="button" @click="fab = false" class="w-full mt-1 py-2 text-xs font-semibold text-gray-500">Cancel</button>
    </div>
</div>
