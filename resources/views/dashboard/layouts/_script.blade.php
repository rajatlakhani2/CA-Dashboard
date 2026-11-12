{{-- Shared behaviour for dashboard layouts: complete / start / snooze without a page reload. --}}
<div id="dash-toast" role="status" aria-live="polite" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex justify-center px-4"></div>
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const snoozeUrl = @json(route('dashboard.inbox.snooze'));
    const toastBox = document.getElementById('dash-toast');

    function toast(message, undo) {
        toastBox.innerHTML = '';
        const el = document.createElement('div');
        el.className = 'pointer-events-auto flex max-w-xl items-center gap-4 rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl';
        const text = document.createElement('span');
        text.textContent = message;
        el.appendChild(text);
        if (undo) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'font-semibold text-indigo-300 hover:text-indigo-200';
            b.textContent = 'Undo';
            b.onclick = undo;
            el.appendChild(b);
        }
        toastBox.appendChild(el);
        clearTimeout(toast._t);
        toast._t = setTimeout(() => { toastBox.innerHTML = ''; }, 6000);
    }

    async function send(url, method, payload) {
        const body = new FormData();
        if (method !== 'POST') body.append('_method', method);
        Object.entries(payload || {}).forEach(([k, v]) => body.append(k, v));
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body,
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res;
    }

    function removeItem(btn) {
        const row = btn.closest('[data-dash-item]');
        if (!row) return null;
        row.style.transition = 'opacity .2s';
        row.style.opacity = '0';
        const parent = row.parentNode, next = row.nextSibling;
        setTimeout(() => row.remove(), 200);
        document.dispatchEvent(new CustomEvent('dash:changed'));
        return () => { row.style.opacity = '1'; parent.insertBefore(row, next); document.dispatchEvent(new CustomEvent('dash:changed')); };
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-dash-action]');
        if (!btn) return;
        e.preventDefault();
        const action = btn.dataset.dashAction;
        btn.disabled = true;
        try {
            if (action === 'snooze') {
                await send(snoozeUrl, 'POST', { key: btn.dataset.key });
                const restore = removeItem(btn);
                toast('Snoozed until tomorrow: ' + btn.dataset.label, async () => {
                    await send(snoozeUrl, 'POST', { key: btn.dataset.key, undo: 1 });
                    restore && restore();
                    btn.disabled = false;
                    toast('Restored.');
                });
            } else if (action === 'start') {
                await send(btn.dataset.url, btn.dataset.method, JSON.parse(btn.dataset.payload || '{}'));
                toast('Started: ' + btn.dataset.label);
                setTimeout(() => window.location.reload(), 700);
            } else {
                await send(btn.dataset.url, btn.dataset.method, JSON.parse(btn.dataset.payload || '{}'));
                removeItem(btn);
                toast('Done: ' + btn.dataset.label);
            }
        } catch (err) {
            btn.disabled = false;
            toast('That didn’t work. Please refresh and try again.');
        }
    });
})();
</script>
