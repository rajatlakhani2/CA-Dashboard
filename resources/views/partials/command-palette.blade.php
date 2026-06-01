<div id="command-palette-backdrop" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden transition-opacity duration-300" aria-hidden="true" onclick="toggleCommandPalette()"></div>

<div id="command-palette-modal" class="fixed inset-0 z-[60] overflow-y-auto p-4 sm:p-6 md:p-20 hidden pointer-events-none">
    <div class="mx-auto max-w-2xl transform divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all pointer-events-auto">
        <div class="relative">
            <svg class="pointer-events-none absolute top-3.5 left-4 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
            <input type="text" id="command-palette-input" class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm" placeholder="Search or type # for actions, &gt; for clients… (Ctrl+K)" role="combobox" aria-expanded="false" aria-controls="options">
        </div>

        <ul id="command-palette-results" class="max-h-96 scroll-py-3 overflow-y-auto p-3" role="listbox"></ul>

        <div id="command-palette-empty" class="py-14 px-6 text-center text-sm sm:px-14 hidden">
            <svg class="mx-auto h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="mt-4 font-semibold text-gray-900">No results found</p>
            <p class="mt-2 text-gray-500">Try a different term, <kbd class="font-semibold">#action</kbd>, or <kbd class="font-semibold">&gt;client</kbd>.</p>
        </div>

        <div class="flex flex-wrap items-center bg-gray-50 py-2.5 px-4 text-xs text-gray-700">
            <kbd class="mx-1 flex h-5 min-w-[1.25rem] px-1 items-center justify-center rounded border bg-white font-semibold border-gray-400 text-gray-900">#</kbd> actions
            <kbd class="mx-1 flex h-5 min-w-[1.25rem] px-1 items-center justify-center rounded border bg-white font-semibold border-gray-400 text-gray-900">&gt;</kbd> clients
            <span class="mx-2 text-gray-300">|</span>
            Esc to close
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
            paletteInput.value = '';
            paletteInput.focus();
            loadPaletteDefaults();
        } else {
            paletteBackdrop.classList.add('hidden');
            paletteModal.classList.add('hidden');
        }
    }

    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            toggleCommandPalette();
        }
        if (e.key === 'Escape' && isOpen) {
            toggleCommandPalette();
        }
    });

    function loadPaletteDefaults() {
        paletteEmpty.classList.add('hidden');
        paletteResults.classList.remove('hidden');
        paletteResults.innerHTML = '<li class="p-3 text-sm text-gray-400">Loading quick actions…</li>';

        fetch('{{ route('search.palette') }}', { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                const sections = [
                    { label: 'Quick actions', items: data.actions || [] },
                    { label: 'Go to', items: data.navigation || [] },
                ];
                renderPaletteSections(sections);
            })
            .catch(() => {
                paletteResults.innerHTML = '<li class="p-3 text-sm text-red-500">Could not load palette.</li>';
            });
    }

    function renderPaletteItem(item) {
        const subtitle = item.subtitle ? `<p class="text-sm text-gray-500 group-hover:text-indigo-700">${item.subtitle}</p>` : '';
        return `
            <li class="group flex cursor-pointer select-none rounded-xl p-3 hover:bg-indigo-50" onclick="window.location.href='${item.url}'">
                <div class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-indigo-100 group-hover:bg-indigo-600 transition-colors">
                    <svg class="h-6 w-6 text-indigo-600 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        ${getIconPath(item.icon)}
                    </svg>
                </div>
                <div class="ml-4 flex-auto">
                    <p class="text-sm font-medium text-gray-700 group-hover:text-indigo-900">${item.title}</p>
                    ${subtitle}
                </div>
            </li>`;
    }

    function renderPaletteSections(sections) {
        let html = '';
        sections.forEach(section => {
            if (!section.items || section.items.length === 0) return;
            html += `<li class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">${section.label}</li>`;
            section.items.forEach(item => { html += renderPaletteItem(item); });
        });
        paletteResults.innerHTML = html || '';
        if (!html) {
            paletteResults.classList.add('hidden');
            paletteEmpty.classList.remove('hidden');
        }
    }

    function renderFlatResults(data) {
        if (data.length === 0) {
            paletteResults.classList.add('hidden');
            paletteEmpty.classList.remove('hidden');
            return;
        }
        paletteEmpty.classList.add('hidden');
        paletteResults.classList.remove('hidden');

        const byCategory = {};
        data.forEach(item => {
            const cat = item.category || 'Results';
            if (!byCategory[cat]) byCategory[cat] = [];
            byCategory[cat].push(item);
        });

        const sections = Object.keys(byCategory).map(label => ({ label, items: byCategory[label] }));
        renderPaletteSections(sections);
    }

    paletteInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();

        if (query.length < 2) {
            if (query.length === 0) {
                loadPaletteDefaults();
            } else {
                paletteResults.innerHTML = '<li class="p-3 text-sm text-gray-400">Type at least 2 characters…</li>';
                paletteEmpty.classList.add('hidden');
                paletteResults.classList.remove('hidden');
            }
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/search/global?query=${encodeURIComponent(query)}`, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => renderFlatResults(data));
        }, 250);
    });

    function getIconPath(iconName) {
        if (iconName === 'home') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>';
        if (iconName === 'users' || iconName === 'user') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>';
        if (iconName === 'plus') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>';
        if (iconName === 'cash') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>';
        if (iconName === 'phone') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>';
        if (iconName === 'sun') return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>';
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>';
    }
</script>
