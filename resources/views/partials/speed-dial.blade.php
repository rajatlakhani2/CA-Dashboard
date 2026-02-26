<div x-data="{ open: false }" class="fixed bottom-6 right-24 z-[9990] font-sans">
    <!-- Options List -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="absolute bottom-16 right-0 mb-2 flex flex-col space-y-2 items-end">

        <!-- New Invoice -->
        <a href="{{ route('invoices.create') }}" class="flex items-center space-x-2 group">
            <span class="bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">New Invoice</span>
            <div class="h-10 w-10 bg-green-500 rounded-full text-white shadow-lg flex items-center justify-center hover:bg-green-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </div>
        </a>

        <!-- New Task -->
        <a href="{{ route('tasks.create') }}" class="flex items-center space-x-2 group">
            <span class="bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">New Task</span>
            <div class="h-10 w-10 bg-blue-500 rounded-full text-white shadow-lg flex items-center justify-center hover:bg-blue-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
        </a>

        <!-- New Client -->
        <a href="{{ route('clients.create') }}" class="flex items-center space-x-2 group">
            <span class="bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">New Client</span>
            <div class="h-10 w-10 bg-indigo-500 rounded-full text-white shadow-lg flex items-center justify-center hover:bg-indigo-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
        </a>
    </div>

    <!-- Main FAB Button -->
    <button @click="open = !open" :class="{'rotate-45': open}" class="h-14 w-14 bg-indigo-600 rounded-full shadow-lg shadow-indigo-500/40 text-white flex items-center justify-center hover:bg-indigo-700 transition-transform duration-200">
        <svg class="w-8 h-8 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
    </button>
</div>