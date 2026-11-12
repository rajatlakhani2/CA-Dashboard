{{-- Shared behaviour for dashboard layouts: actions without reload, payment pop-up,
     Work board drag-and-drop, and quiet auto-refresh. --}}
<div id="dash-toast" role="status" aria-live="polite" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex justify-center px-4"></div>
<p id="dash-refreshed" class="mt-2 text-center text-xs text-slate-500" aria-live="off"></p>
@php
    $dashTaskStatus = [
        'over' => \App\Models\Task::STATUS_PENDING,
        'week' => \App\Models\Task::STATUS_PENDING,
        'waiting' => \App\Models\Task::STATUS_ON_HOLD,
        'doing' => \App\Models\Task::STATUS_IN_PROGRESS,
        'done' => \App\Models\Task::STATUS_COMPLETED,
    ];
@endphp
<script>
(function () {
    if (window.__dashLayoutScript) return;          // survive auto-refresh re-renders
    window.__dashLayoutScript = true;

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
    const snoozeUrl = @json(route('dashboard.inbox.snooze'));
    const TASK_STATUS = @json($dashTaskStatus);
    const REFRESH_MS = 3 * 60 * 1000;
    let lastInteraction = Date.now();
    let busy = false;

    // ---------------------------------------------------------------- toast
    function toast(message, undo) {
        const box = document.getElementById('dash-toast');
        if (!box) return;
        box.innerHTML = '';
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
        box.appendChild(el);
        clearTimeout(toast._t);
        toast._t = setTimeout(() => { box.innerHTML = ''; }, 6000);
    }

    // ---------------------------------------------------------------- requests
    async function send(url, method, payload) {
        const body = new FormData();
        if (method && method !== 'POST') body.append('_method', method);
        Object.entries(payload || {}).forEach(([k, v]) => body.append(k, v));
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body,
            credentials: 'same-origin',
        });
        if (!res.ok) {
            let msg = 'HTTP ' + res.status;
            try { const j = await res.json(); msg = j.message || Object.values(j.errors || {})[0]?.[0] || msg; } catch (e) {}
            const err = new Error(msg); err.status = res.status; throw err;
        }
        return res;
    }

    function removeItem(el) {
        const row = el.closest('[data-dash-item]');
        if (!row) return null;
        const parent = row.parentNode, next = row.nextSibling;
        row.style.transition = 'opacity .2s';
        row.style.opacity = '0';
        setTimeout(() => { row.remove(); recount(); }, 200);
        return () => { row.style.opacity = '1'; parent.insertBefore(row, next); recount(); };
    }

    function recount() {
        document.querySelectorAll('[data-drop-col]').forEach(col => {
            const head = col.parentElement.querySelector('[data-col-count]');
            const n = col.querySelectorAll('[data-dash-item]').length;
            if (head) head.textContent = n;
            const empty = col.querySelector('[data-empty]');
            if (empty) empty.hidden = n > 0;
        });
    }

    // ---------------------------------------------------------------- payment pop-up
    let payFrom = null;
    function openPay(trigger) {
        const dlg = document.getElementById('dash-pay-dialog');
        if (!dlg) { toast('You don’t have access to record payments.'); return; }
        const p = JSON.parse(trigger.dataset.pay || '{}');
        const form = dlg.querySelector('[data-pay-form]');
        form.reset();
        form.invoice_id.value = p.invoice_id;
        form.amount.value = p.balance;
        form.amount.max = p.balance;
        dlg.querySelector('[data-pay-summary]').textContent = p.client + ' · Invoice ' + p.number + ' · ₹' + Number(p.balance).toLocaleString('en-IN') + ' due';
        dlg.querySelector('[data-pay-error]').classList.add('hidden');
        payFrom = trigger;
        dlg.showModal();
        form.amount.focus();
    }

    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('[data-pay-form]');
        if (!form) return;
        e.preventDefault();
        const dlg = form.closest('dialog');
        const btn = form.querySelector('[data-pay-submit]');
        const errBox = form.querySelector('[data-pay-error]');
        btn.disabled = true;
        try {
            const res = await send(dlg.dataset.storeUrl, 'POST', Object.fromEntries(new FormData(form)));
            const data = await res.json();
            dlg.close();
            if (payFrom) {
                if (data.balance <= 0.5) {
                    removeItem(payFrom);
                } else {
                    const p = JSON.parse(payFrom.dataset.pay); p.balance = data.balance;
                    payFrom.dataset.pay = JSON.stringify(p);
                }
            }
            toast(data.message + (data.balance > 0.5 ? ' · ₹' + Number(data.balance).toLocaleString('en-IN') + ' still due' : ' · fully paid'));
        } catch (err) {
            errBox.textContent = err.message.startsWith('HTTP') ? 'Couldn’t save the payment. Please try again.' : err.message;
            errBox.classList.remove('hidden');
        } finally {
            btn.disabled = false;
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-pay-cancel]')) e.target.closest('dialog').close();
    });

    // ---------------------------------------------------------------- buttons
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-dash-action]');
        if (!btn) return;
        e.preventDefault();
        const action = btn.dataset.dashAction;
        if (action === 'pay') { openPay(btn); return; }
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
                setTimeout(() => refresh(true), 500);
            } else {
                await send(btn.dataset.url, btn.dataset.method, JSON.parse(btn.dataset.payload || '{}'));
                removeItem(btn);
                toast('Done: ' + btn.dataset.label);
            }
        } catch (err) {
            btn.disabled = false;
            toast('That didn’t work: ' + err.message);
        }
    });

    // ---------------------------------------------------------------- board drag-and-drop
    let dragged = null;
    document.addEventListener('dragstart', (e) => {
        const card = e.target.closest?.('[data-dash-item][draggable="true"]');
        if (!card) return;
        dragged = card;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.dataset.label || '');
        setTimeout(() => card.classList.add('opacity-40'), 0);
    });
    document.addEventListener('dragend', () => {
        dragged?.classList.remove('opacity-40');
        dragged = null;
        document.querySelectorAll('[data-drop-col]').forEach(c => c.classList.remove('ring-2', 'ring-teal-500', 'bg-teal-50'));
    });
    document.addEventListener('dragover', (e) => {
        const col = e.target.closest?.('[data-drop-col]');
        if (!col || !dragged) return;
        e.preventDefault();
        document.querySelectorAll('[data-drop-col]').forEach(c => c.classList.toggle('ring-2', c === col));
        col.classList.add('ring-teal-500');
    });
    document.addEventListener('drop', async (e) => {
        const col = e.target.closest?.('[data-drop-col]');
        if (!col || !dragged) return;
        e.preventDefault();
        const card = dragged;
        const target = col.dataset.dropCol;
        const from = card.closest('[data-drop-col]');
        if (from === col) return;
        lastInteraction = Date.now();

        try {
            if (card.dataset.type === 'task') {
                await send(card.dataset.statusUrl, 'PATCH', { status: TASK_STATUS[target] });
                if (target === 'done') { removeItem(card); toast('Done: ' + card.dataset.label); return; }
                col.prepend(card);
                recount();
                toast('Moved: ' + card.dataset.label);
            } else if (target === 'done') {
                if (card.dataset.pay) { openPay(card); return; }
                if (!card.dataset.completeUrl) { toast('Open this item to update it.'); return; }
                await send(card.dataset.completeUrl, card.dataset.completeMethod, JSON.parse(card.dataset.completePayload || '{}'));
                removeItem(card);
                toast('Done: ' + card.dataset.label);
            } else {
                toast('Only tasks can move between columns. Drag filings, payments and documents to “done”.');
            }
        } catch (err) {
            toast('That didn’t work: ' + err.message);
        }
    });

    // ---------------------------------------------------------------- auto-refresh
    ['click', 'keydown', 'input', 'scroll', 'touchstart'].forEach(ev =>
        document.addEventListener(ev, () => { lastInteraction = Date.now(); }, { passive: true }));

    async function refresh(force) {
        const root = document.querySelector('[data-dashboard-layout]');
        if (!root || busy) return;
        const idle = Date.now() - lastInteraction > 60 * 1000;
        const modalOpen = document.querySelector('dialog[open]') || dragged;
        if (!force && (document.hidden || !idle || modalOpen)) return;
        busy = true;
        try {
            const res = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok || res.redirected) return;
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const fresh = doc.querySelector('[data-dashboard-layout]');
            if (!fresh || fresh.dataset.dashboardLayout !== root.dataset.dashboardLayout) return;
            const y = window.scrollY;
            root.innerHTML = fresh.innerHTML;          // Alpine picks up the new nodes itself
            window.scrollTo(0, y);
            const stamp = document.getElementById('dash-refreshed');
            if (stamp) stamp.textContent = 'Updated ' + new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            // Network blip: try again next time.
        } finally {
            busy = false;
        }
    }
    window.dashRefresh = refresh;
    setInterval(refresh, REFRESH_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
</script>
