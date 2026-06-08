@php
    $help = $dashboardHelp ?? [];
    $prompts = $help['quickPrompts'] ?? [];
    $brandName = $help['brandName'] ?? 'Vouchex';
@endphp
<div
    id="dashboard-help-chat"
    class="dashboard-help-chat"
    x-data="dashboardHelpChat({{ \Illuminate\Support\Js::from([
        'chatUrl' => $help['chatUrl'] ?? '',
        'prompts' => $prompts,
        'brandName' => $brandName,
    ]) }})"
    x-cloak
>
    <button
        type="button"
        @click="toggle()"
        class="dashboard-help-chat__tab"
        :aria-expanded="open"
        aria-controls="dashboard-help-panel"
        title="Dashboard help"
    >
        <span class="dashboard-help-chat__tab-emoji">🤖</span>
        <span class="dashboard-help-chat__tab-label">Help</span>
    </button>

    <aside
        id="dashboard-help-panel"
        class="dashboard-help-chat__panel"
        :class="open ? 'is-open' : ''"
        role="dialog"
        aria-label="Dashboard help assistant"
    >
        <header class="dashboard-help-chat__header">
            <div>
                <p class="dashboard-help-chat__eyebrow">Dashboard guide</p>
                <h2 class="dashboard-help-chat__title">{{ $brandName }} Help</h2>
                <p class="dashboard-help-chat__tagline">Ask how anything works — I'll guide you there</p>
            </div>
            <button type="button" @click="toggle()" class="dashboard-help-chat__close" aria-label="Close help">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>

        <div class="dashboard-help-chat__messages" x-ref="messages">
            <template x-for="(msg, idx) in messages" :key="idx">
                <div class="dashboard-help-chat__row" :class="msg.role === 'user' ? 'is-user' : 'is-bot'">
                    <template x-if="msg.role === 'bot'">
                        <span class="dashboard-help-chat__avatar">🤖</span>
                    </template>
                    <div class="dashboard-help-chat__bubble">
                        <p x-text="msg.text"></p>
                        <template x-if="msg.url">
                            <a :href="msg.url" class="dashboard-help-chat__link">Open →</a>
                        </template>
                    </div>
                </div>
            </template>
            <div x-show="loading" class="dashboard-help-chat__row is-bot">
                <span class="dashboard-help-chat__avatar">🤖</span>
                <div class="dashboard-help-chat__bubble is-typing">Thinking…</div>
            </div>
        </div>

        <div class="dashboard-help-chat__prompts" x-show="messages.length <= 1">
            <p class="dashboard-help-chat__prompts-label">Quick questions</p>
            <div class="dashboard-help-chat__prompt-grid">
                <template x-for="item in prompts" :key="item.prompt">
                    <button type="button" class="dashboard-help-chat__chip" @click="ask(item.prompt)" x-text="item.label"></button>
                </template>
            </div>
        </div>

        <form @submit.prevent="submit()" class="dashboard-help-chat__form">
            <input
                type="text"
                x-model="input"
                x-ref="input"
                placeholder="Ask about Mission Control, billing, Ctrl+K…"
                class="dashboard-help-chat__input"
                autocomplete="off"
            >
            <button type="submit" class="dashboard-help-chat__send" :disabled="loading || !input.trim()">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </form>
    </aside>
</div>

<style>
    .dashboard-help-chat { font-family: inherit; }
    .dashboard-help-chat__tab {
        position: fixed;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        z-index: 58;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
        padding: 0.85rem 0.55rem;
        border: none;
        border-radius: 14px 0 0 14px;
        background: linear-gradient(180deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
        box-shadow: -4px 0 24px rgba(79, 70, 229, 0.35);
        cursor: pointer;
        transition: transform 0.2s ease, padding 0.2s ease;
    }
    .dashboard-help-chat__tab:hover { padding-right: 0.75rem; }
    .dashboard-help-chat__tab-emoji { font-size: 1.35rem; line-height: 1; }
    .dashboard-help-chat__tab-label {
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        writing-mode: vertical-rl;
        text-orientation: mixed;
        transform: rotate(180deg);
    }
    .dashboard-help-chat__panel {
        position: fixed;
        top: 0;
        right: 0;
        z-index: 59;
        width: min(100vw, 22rem);
        height: 100dvh;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-left: 1px solid #e2e8f0;
        box-shadow: -12px 0 40px rgba(15, 23, 42, 0.12);
        transform: translateX(100%);
        transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dashboard-help-chat__panel.is-open { transform: translateX(0); }
    .dashboard-help-chat__header {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1.1rem 1rem 1rem;
        background: linear-gradient(135deg, #312e81 0%, #4338ca 55%, #6366f1 100%);
        color: #fff;
    }
    .dashboard-help-chat__eyebrow {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        opacity: 0.85;
    }
    .dashboard-help-chat__title { font-size: 1.2rem; font-weight: 900; margin-top: 0.15rem; }
    .dashboard-help-chat__tagline { font-size: 0.85rem; opacity: 0.9; margin-top: 0.25rem; line-height: 1.35; }
    .dashboard-help-chat__close {
        flex-shrink: 0;
        height: 2rem;
        width: 2rem;
        border-radius: 0.5rem;
        border: none;
        background: rgba(255,255,255,0.15);
        color: #fff;
        cursor: pointer;
    }
    .dashboard-help-chat__messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        background: #f8fafc;
    }
    .dashboard-help-chat__row { display: flex; gap: 0.5rem; align-items: flex-start; }
    .dashboard-help-chat__row.is-user { justify-content: flex-end; }
    .dashboard-help-chat__avatar {
        flex-shrink: 0;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 9999px;
        background: #eef2ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }
    .dashboard-help-chat__bubble {
        max-width: 88%;
        padding: 0.75rem 0.9rem;
        border-radius: 14px;
        font-size: 0.95rem;
        line-height: 1.5;
        white-space: pre-line;
    }
    .dashboard-help-chat__row.is-bot .dashboard-help-chat__bubble {
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #334155;
        border-top-left-radius: 4px;
    }
    .dashboard-help-chat__row.is-user .dashboard-help-chat__bubble {
        background: #4f46e5;
        color: #fff;
        border-top-right-radius: 4px;
    }
    .dashboard-help-chat__bubble.is-typing { color: #64748b; font-style: italic; }
    .dashboard-help-chat__link {
        display: inline-block;
        margin-top: 0.5rem;
        font-size: 0.85rem;
        font-weight: 700;
        color: #4f46e5;
        text-decoration: none;
    }
    .dashboard-help-chat__row.is-user .dashboard-help-chat__link { color: #e0e7ff; }
    .dashboard-help-chat__prompts {
        padding: 0 1rem 0.75rem;
        background: #f8fafc;
        border-top: 1px solid #eef2f7;
    }
    .dashboard-help-chat__prompts-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 0.5rem;
    }
    .dashboard-help-chat__prompt-grid { display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .dashboard-help-chat__chip {
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        border-radius: 9999px;
        border: 1px solid #c7d2fe;
        background: #eef2ff;
        color: #4338ca;
        cursor: pointer;
    }
    .dashboard-help-chat__chip:hover { background: #e0e7ff; }
    .dashboard-help-chat__form {
        display: flex;
        gap: 0.5rem;
        padding: 0.85rem 1rem 1rem;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }
    .dashboard-help-chat__input {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 9999px;
        padding: 0.65rem 1rem;
        font-size: 0.95rem;
        outline: none;
    }
    .dashboard-help-chat__input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }
    .dashboard-help-chat__send {
        flex-shrink: 0;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 9999px;
        border: none;
        background: #4f46e5;
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .dashboard-help-chat__send:disabled { opacity: 0.45; cursor: not-allowed; }
    @media (max-width: 640px) {
        .dashboard-help-chat__panel { width: 100vw; }
        .dashboard-help-chat__tab { top: auto; bottom: 5.5rem; transform: none; border-radius: 14px 0 0 14px; }
    }
</style>

<script>
function dashboardHelpChat(config) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    return {
        open: false,
        input: '',
        loading: false,
        messages: [{
            role: 'bot',
            text: '👋 Hi! I\'m your ' + config.brandName + ' guide. Ask how Mission Control, My Day, billing, invoices, or Ctrl+K work — or tap a quick question below.',
            url: null,
        }],
        prompts: config.prompts || [],

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },

        ask(text) {
            this.input = text;
            this.submit();
        },

        async submit() {
            const text = this.input.trim();
            if (!text || this.loading) return;

            this.messages.push({ role: 'user', text, url: null });
            this.input = '';
            this.loading = true;
            this.scroll();

            try {
                const res = await fetch(config.chatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        message: text,
                        page: window.location.pathname,
                    }),
                });

                const data = await res.json();
                this.messages.push({
                    role: 'bot',
                    text: data.text || 'Sorry, I could not answer that.',
                    url: data.url || null,
                });
            } catch {
                this.messages.push({
                    role: 'bot',
                    text: 'Connection issue — try again, or press Ctrl+K to search the app.',
                    url: null,
                });
            } finally {
                this.loading = false;
                this.scroll();
            }
        },

        scroll() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },
    };
}
</script>
