<div id="command-palette-backdrop" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden transition-opacity duration-300" aria-hidden="true" onclick="toggleCommandPalette()"></div>

<div id="command-palette-modal" class="fixed inset-0 z-[60] overflow-y-auto p-4 sm:p-6 md:p-20 hidden pointer-events-none">
    <div class="mx-auto max-w-2xl transform divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all pointer-events-auto">
        <div class="relative">
            <svg class="pointer-events-none absolute top-3.5 left-4 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
            <input type="text" id="command-palette-input" class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm" placeholder="Search clients, tasks, or actions... (Ctrl+K)" role="combobox" aria-expanded="false" aria-controls="options">
        </div>

        <!-- Default State (Quick Links) -->
        <ul id="command-palette-results" class="max-h-96 scroll-py-3 overflow-y-auto p-3" role="listbox">
            @if(isset($recents))
            <!-- Can populate recent items here later -->
            @endif
            <li class="group flex cursor-default select-none rounded-xl p-3 hover:bg-gray-100">
                <div class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-indigo-500">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div class="ml-4 flex-auto">
                    <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Start typing to search...</p>
                    <p class="text-sm text-gray-500 group-hover:text-gray-700">Clients, Navigation, Tasks</p>
                </div>
            </li>
        </ul>

        <!-- Empty State -->
        <div id="command-palette-empty" class="py-14 px-6 text-center text-sm sm:px-14 hidden">
            <svg class="mx-auto h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="mt-4 font-semibold text-gray-900">No results found</p>
            <p class="mt-2 text-gray-500">No components found for this search term. Please try again.</p>
        </div>

        <div class="flex flex-wrap items-center bg-gray-50 py-2.5 px-4 text-xs text-gray-700">
            Type <kbd class="mx-1 flex h-5 w-5 items-center justify-center rounded border bg-white font-semibold sm:mx-2 border-gray-400 text-gray-900">#</kbd> for actions, <kbd class="mx-1 flex h-5 w-5 items-center justify-center rounded border bg-white font-semibold sm:mx-2 border-gray-400 text-gray-900">&gt;</kbd> for clients.
        </div>
    </div>
</div>

<script>
    let paletteBackdrop = document.getElementById('command-palette-backdrop');
    let paletteModal = document.getElementById('command-palette-modal');
    let paletteInput = document.getElementById('command-palette-input');
    let paletteResults = document.getElementById('command-palette-results');
    let paletteEmpty = document.getElementById('command-palette-empty');
    let isOpen = false;
    let debounceTimer;

    function toggleCommandPalette() {
        isOpen = !isOpen;
        if (isOpen) {
            paletteBackdrop.classList.remove('hidden');
            paletteModal.classList.remove('hidden');
            paletteInput.focus();
            // Reset
            paletteInput.value = '';
            // Perform one initial search or clear? Let's just focus.
        } else {
            paletteBackdrop.classList.add('hidden');
            paletteModal.classList.add('hidden');
        }
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            toggleCommandPalette();
        }
        if (e.key === 'Escape' && isOpen) {
            toggleCommandPalette();
        }
    });

    // Search Logic
    paletteInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const query = e.target.value;

        if (query.length < 2) {
            // Provide default state or empty
            paletteResults.innerHTML = `
             <li class="group flex cursor-default select-none rounded-xl p-3 hover:bg-gray-100">
                <div class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-indigo-500">
                     <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                     </svg>
                </div>
                <div class="ml-4 flex-auto">
                    <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Start typing to search...</p>
                </div>
            </li>`;
            paletteEmpty.classList.add('hidden');
            paletteResults.classList.remove('hidden');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/search/global?query=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        paletteResults.classList.add('hidden');
                        paletteEmpty.classList.remove('hidden');
                    } else {
                        paletteEmpty.classList.add('hidden');
                        paletteResults.classList.remove('hidden');

                        let html = '';
                        // Group by category if we want, but simple list for now
                        data.forEach(item => {
                            html += `
                            <li class="group flex cursor-pointer select-none rounded-xl p-3 hover:bg-indigo-50" onclick="window.location.href='${item.url}'">
                                <div class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-indigo-100 group-hover:bg-indigo-600 transition-colors">
                                    <svg class="h-6 w-6 text-indigo-600 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <!-- Dynamic Icon logic simplified -->
                                        ${getIconPath(item.icon)}
                                    </svg>
                                </div>
                                <div class="ml-4 flex-auto">
                                    <p class="text-sm font-medium text-gray-700 group-hover:text-indigo-900">
                                        <span class="text-xs font-bold uppercase text-gray-400 group-hover:text-indigo-400 mr-2">${item.category}</span>
                                        ${item.title}
                                    </p>
                                    ${item.subtitle ? `<p class="text-sm text-gray-500 group-hover:text-indigo-700">${item.subtitle}</p>` : ''}
                                </div>
                            </li>
                            `;
                        });
                        paletteResults.innerHTML = html;
                    }
                });
        }, 300);
    });

    function getIconPath(iconName) {
        // Simple mapping or default
        // In a real app we might use a robust icon set.
        // For now, return generic path or specific ones based on name.
        if (iconName === 'home') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>';
        if (iconName === 'users') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>';
        if (iconName === 'plus') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>';
        // Default
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>';
    }
</script>