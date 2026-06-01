@php
    $aiEnabled = app(\App\Services\Intelligence\AiAssistantService::class)->isEnabled();
@endphp
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="clientAiAssistant({{ \Illuminate\Support\Js::from([
    'summarize' => route('clients.ai.summarize', $client),
    'explain' => route('clients.ai.explain-overdue', $client),
    'whatsapp' => route('clients.ai.draft-whatsapp', $client),
    'enabled' => $aiEnabled,
    'disclaimer' => config('ai.disclaimer'),
]) }})">
    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-bold text-slate-900">AI assistant</h3>
            <p class="text-xs text-slate-500">Operational summaries only — review before client contact.</p>
        </div>
        @unless($aiEnabled)
        <span class="text-xs font-medium text-amber-700 bg-amber-50 px-2 py-1 rounded-full">Set AI_ENABLED + OPENAI_API_KEY</span>
        @endunless
    </div>
    <div class="p-4 flex flex-wrap gap-2">
        <button type="button" @click="run('summarize')" :disabled="loading"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
            Summarize client
        </button>
        <button type="button" @click="run('explain')" :disabled="loading"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-50">
            Explain overdue stack
        </button>
        <button type="button" @click="run('whatsapp')" :disabled="loading"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-50">
            Draft WhatsApp
        </button>
    </div>
    <div class="px-4 pb-4" x-show="loading" x-cloak>
        <p class="text-sm text-slate-500 animate-pulse">Thinking…</p>
    </div>
    <div class="px-4 pb-4" x-show="error" x-cloak>
        <p class="text-sm text-red-600" x-text="error"></p>
    </div>
    <div class="px-4 pb-4" x-show="output" x-cloak>
        <pre class="text-sm text-slate-800 whitespace-pre-wrap font-sans bg-slate-50 rounded-lg p-3 border border-slate-100" x-text="output"></pre>
        <p class="text-[10px] text-slate-400 mt-2" x-text="disclaimer"></p>
        <div class="mt-2 flex gap-2" x-show="lastAction === 'whatsapp' && output">
            <button type="button" @click="copyOutput()"
                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Copy to clipboard</button>
            <span class="text-xs text-slate-400">Paste into WhatsApp manually — not auto-sent.</span>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function clientAiAssistant(config) {
    return {
        urls: config,
        enabled: config.enabled,
        disclaimer: config.disclaimer,
        loading: false,
        output: '',
        error: '',
        lastAction: '',
        async run(action) {
            this.loading = true;
            this.error = '';
            this.output = '';
            this.lastAction = action === 'whatsapp' ? 'whatsapp' : '';
            const url = action === 'summarize' ? this.urls.summarize
                : action === 'explain' ? this.urls.explain
                : this.urls.whatsapp;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: action === 'whatsapp' ? JSON.stringify({ tone: 'polite' }) : '{}',
                });
                const data = await res.json();
                if (data.ok && data.text) {
                    this.output = data.text;
                    if (data.disclaimer) this.disclaimer = data.disclaimer;
                } else {
                    this.error = data.error || 'AI request failed.';
                }
            } catch (e) {
                this.error = 'Network error.';
            } finally {
                this.loading = false;
            }
        },
        copyOutput() {
            if (this.output) navigator.clipboard.writeText(this.output);
        },
    };
}
</script>
@endpush
@endonce
