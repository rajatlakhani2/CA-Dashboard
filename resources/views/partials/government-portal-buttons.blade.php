<div
    class="hidden md:flex items-center gap-1.5 mr-1"
    x-data="govPortalPicker()"
    x-cloak
>
    @foreach(\App\Support\GovernmentPortals::all() as $portal)
    <button
        type="button"
        @click="openPortal(@js($portal['id']), @js($portal['label']))"
        class="p-1 rounded-lg border border-transparent hover:border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-200 transition"
        title="{{ $portal['label'] }}"
        aria-label="{{ $portal['label'] }} portal"
    >
        <img
            src="{{ $portal['logo'] }}"
            alt="{{ $portal['label'] }}"
            class="h-7 w-7 object-contain"
            loading="lazy"
        >
    </button>
    @endforeach

    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[120] flex items-start justify-center bg-slate-900/45 p-4 pt-20"
        style="display: none;"
        @keydown.escape.window="close()"
    >
        <div
            @click.away="close()"
            class="w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 overflow-hidden"
        >
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900" x-text="portalLabel + ' — Select client'"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Clients with saved credentials for this portal</p>
                </div>
                <button type="button" @click="close()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="px-5 py-3 border-b border-slate-50">
                <input
                    type="search"
                    x-model="search"
                    placeholder="Filter clients…"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100"
                >
            </div>

            <div class="max-h-[min(24rem,55vh)] overflow-y-auto">
                <template x-if="loading">
                    <p class="px-5 py-8 text-sm text-slate-500 text-center">Loading clients…</p>
                </template>
                <template x-if="!loading && filteredClients().length === 0">
                    <p class="px-5 py-8 text-sm text-slate-500 text-center">
                        <span x-text="'No clients with ' + portalLabel + ' credentials found.'"></span><br>
                        Add them under Client Passwords &amp; Credentials.
                    </p>
                </template>
                <template x-for="client in filteredClients()" :key="client.credential_id">
                    <button
                        type="button"
                        @click="launchClient(client.launch_url)"
                        class="w-full text-left px-5 py-3 border-b border-slate-50 hover:bg-indigo-50 transition"
                    >
                        <div class="font-semibold text-slate-900" x-text="client.client_name"></div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            <span x-text="client.portal_name"></span>
                            <span x-show="client.username"> · </span>
                            <span x-text="client.username" class="font-mono"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
    function govPortalPicker() {
        return {
            open: false,
            loading: false,
            portalId: null,
            portalLabel: '',
            search: '',
            clients: [],
            async openPortal(id, label) {
                this.portalId = id;
                this.portalLabel = label;
                this.search = '';
                this.open = true;
                this.loading = true;
                this.clients = [];
                try {
                    const response = await fetch(`/gov-portals/${id}/clients`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error('Failed to load clients');
                    const data = await response.json();
                    this.clients = data.clients || [];
                } catch (error) {
                    this.clients = [];
                } finally {
                    this.loading = false;
                }
            },
            filteredClients() {
                const term = this.search.trim().toLowerCase();
                if (!term) return this.clients;
                return this.clients.filter(function (client) {
                    return [
                        client.client_name,
                        client.client_code,
                        client.group_name,
                        client.portal_name,
                        client.username,
                    ].some(function (value) {
                        return value && String(value).toLowerCase().includes(term);
                    });
                });
            },
            launchClient(url) {
                window.open(url, '_blank', 'noopener,noreferrer');
                this.close();
            },
            close() {
                this.open = false;
                this.portalId = null;
                this.clients = [];
                this.search = '';
            },
        };
    }
</script>
