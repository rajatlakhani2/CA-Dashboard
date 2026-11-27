{{-- Phones only: swipe gestures, the quick-action panel and pull-to-refresh for the Work board.
     Laptops keep drag-and-drop and the buttons on each card. Uses window.dashApi from _script. --}}
@php
    $phoneTaskStatus = [
        'todo' => \App\Models\Task::STATUS_PENDING,
        'waiting' => \App\Models\Task::STATUS_ON_HOLD,
        'doing' => \App\Models\Task::STATUS_IN_PROGRESS,
    ];
@endphp
<dialog id="dash-sheet" class="dash-sheet" aria-label="Item actions"
    data-reassign-url="{{ Route::has('workload.reassign') ? route('workload.reassign') : '' }}"
    data-staff='@json(array_values($reassignStaff ?? []))'
    data-today="{{ now()->toDateString() }}">
    <div data-sheet-body></div>
</dialog>



<script>
(function () {
    if (window.__dashPhoneBoard) return;
    window.__dashPhoneBoard = true;

    const phone = window.matchMedia('(max-width: 767px)');
    const STATUS = @json($phoneTaskStatus);
    const api = () => window.dashApi;
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const data = (card) => { try { return JSON.parse(card.dataset.sheet || '{}'); } catch (e) { return {}; } };
    const vibrate = (ms) => { try { navigator.vibrate && navigator.vibrate(ms); } catch (e) {} };
    let suppressClick = 0;

    // ------------------------------------------------------------ actions
    async function markDone(card, fromSwipe) {
        const d = data(card);
        if (d.pay) { api().openPay(card); return; }
        if (!d.complete_url) { if (!fromSwipe) location.href = d.link_url || d.open_url; else openSheet(card); return; }
        if (d.type !== 'task' && fromSwipe) { openSheet(card, true); return; }   // filings etc.: confirm in the panel
        try {
            await api().send(d.complete_url, d.complete_method, d.complete_payload || {});
            const restore = api().removeItem(card);
            vibrate(15);
            const undo = d.type === 'task' ? async () => {
                await api().send(d.complete_url, 'PATCH', { status: STATUS[d.stage] || STATUS.todo });
                restore && restore();
                api().toast('Restored.');
            } : null;
            api().toast('Done: ' + d.title, undo);
        } catch (err) {
            api().toast('That didn’t work: ' + err.message);
        }
    }

    async function snooze(card) {
        const d = data(card);
        try {
            await api().send(@json(route('dashboard.inbox.snooze')), 'POST', { key: d.key });
            const restore = api().removeItem(card);
            vibrate(10);
            api().toast('Moved to tomorrow: ' + d.title, async () => {
                await api().send(@json(route('dashboard.inbox.snooze')), 'POST', { key: d.key, undo: 1 });
                restore && restore();
                api().toast('Restored.');
            });
        } catch (err) {
            api().toast('That didn’t work: ' + err.message);
        }
    }

    async function setStage(card, stage) {
        const d = data(card);
        try {
            await api().send(d.complete_url, 'PATCH', { status: STATUS[stage] });
            const target = { todo: d.over ? 'over' : 'week', waiting: 'waiting', doing: 'doing' }[stage];
            const col = document.querySelector('[data-drop-col="' + target + '"]');
            d.stage = stage; d.start = stage === 'todo';
            card.dataset.sheet = JSON.stringify(d);
            card.dataset.stage = stage;
            if (col) { col.prepend(card); }
            api().recount();
            api().toast({ doing: 'Started: ', waiting: 'Waiting on client: ', todo: 'Back to to-do: ' }[stage] + d.title);
        } catch (err) {
            api().toast('That didn’t work: ' + err.message);
        }
    }

    // ------------------------------------------------------------ quick-action panel
    function waNumber(phoneNo) {
        let n = String(phoneNo || '').replace(/\D/g, '');
        if (n.length === 10) n = '91' + n;
        return n;
    }

    function openSheet(card, confirmDone) {
        const dlg = document.getElementById('dash-sheet');
        if (!dlg) return;
        const d = data(card);
        const staff = JSON.parse(dlg.dataset.staff || '[]');
        const body = dlg.querySelector('[data-sheet-body]');
        const isTask = d.type === 'task';
        let primary = '';
        if (d.pay) primary = '<button type="button" class="btn btn-green" data-s="pay">Record payment</button>';
        else if (d.complete_url) primary = '<button type="button" class="btn btn-green" data-s="done">✓ ' + esc(d.complete_label || 'Mark done') + '</button>';
        else if (d.link_url) primary = '<a class="btn btn-green" href="' + esc(d.link_url) + '">' + esc(d.complete_label || 'Open') + '</a>';

        const rows = [];
        if (isTask && d.stage !== 'doing') rows.push('<button type="button" class="btn" data-s="stage" data-stage="doing">▶ Start</button>');
        if (isTask && d.stage !== 'waiting') rows.push('<button type="button" class="btn" data-s="stage" data-stage="waiting">⏸ Waiting on client</button>');
        if (isTask && d.stage !== 'todo') rows.push('<button type="button" class="btn" data-s="stage" data-stage="todo">↩ Back to to-do</button>');
        rows.push('<button type="button" class="btn" data-s="snooze">→ Tomorrow</button>');
        if (d.phone) {
            rows.push('<a class="btn" href="tel:' + esc(String(d.phone).replace(/[^\d+]/g, '')) + '">📞 Call client</a>');
            rows.push('<a class="btn" href="https://wa.me/' + esc(waNumber(d.phone)) + '" target="_blank" rel="noopener">💬 WhatsApp client</a>');
        }
        if (d.remind_url) rows.push('<button type="button" class="btn" data-s="remind">Send WhatsApp reminder</button>');

        let tools = '';
        if (isTask && d.note_url) {
            tools += '<details class="mt-3 rounded-2xl border border-slate-200 p-3"><summary class="cursor-pointer text-[15px] font-semibold">📝 Add a note</summary>'
                + '<textarea rows="3" maxlength="2000" class="mt-3" data-note placeholder="e.g. Called client, documents coming Monday"></textarea>'
                + '<button type="button" class="btn btn-primary mt-2" data-s="note">Save note</button></details>';
        }
        if (isTask && d.time_url) {
            const hours = [0.25, 0.5, 1, 1.5, 2, 3, 4, 6, 8].map(h => '<option value="' + h + '"' + (h === 1 ? ' selected' : '') + '>' + (h < 1 ? (h * 60) + ' min' : h + (h === 1 ? ' hour' : ' hours')) + '</option>').join('');
            tools += '<details class="mt-3 rounded-2xl border border-slate-200 p-3"><summary class="cursor-pointer text-[15px] font-semibold">⏱ Log time today</summary>'
                + '<select class="mt-3" data-hours>' + hours + '</select>'
                + '<button type="button" class="btn btn-primary mt-2" data-s="time">Log time</button></details>';
        }
        if (d.can_reassign && staff.length && dlg.dataset.reassignUrl) {
            const opts = '<option value="0">Unassigned</option>' + staff.map(s => '<option value="' + esc(s.id) + '"' + (String(s.id) === String(d.who_id) ? ' selected' : '') + '>' + esc(s.name) + '</option>').join('');
            tools += '<details class="mt-3 rounded-2xl border border-slate-200 p-3"><summary class="cursor-pointer text-[15px] font-semibold">👤 Reassign</summary>'
                + '<select class="mt-3" data-assignee>' + opts + '</select>'
                + '<button type="button" class="btn btn-primary mt-2" data-s="reassign">Reassign</button></details>';
        }

        body.innerHTML =
            '<div class="px-5 pb-8 pt-3">'
            + '<div class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-slate-300"></div>'
            + '<div class="flex items-center justify-between gap-2 text-xs"><span class="flex items-center gap-1.5"><span class="rounded-md bg-slate-100 px-2 py-0.5 font-bold text-slate-700">' + esc(d.kind) + '</span>' + (d.priority ? '<span class="cd-prio rounded-md border px-1.5 py-0.5 font-bold ' + ({High: 'border-red-200 bg-red-50 text-red-700', Medium: 'border-amber-200 bg-amber-50 text-amber-800'}[d.priority] || 'border-slate-200 bg-slate-50 text-slate-600') + '">' + esc(d.priority) + ' priority</span>' : '') + '</span>'
            + '<span class="' + (d.over ? 'font-semibold text-red-600' : 'text-slate-500') + '">' + esc(d.due) + '</span></div>'
            + '<h2 class="mt-2 text-lg font-bold leading-snug">' + esc(d.title) + '</h2>'
            + '<p class="text-sm text-slate-500">' + esc(d.client) + (d.who ? ' · ' + esc(d.who) : '') + '</p>'
            + (confirmDone ? '<p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">Confirm to mark this as done.</p>' : '')
            + '<div class="mt-4 space-y-2.5">' + primary + '<div class="grid grid-cols-2 gap-2.5">' + rows.join('') + '</div></div>'
            + tools
            + '<div class="mt-4 grid grid-cols-2 gap-2.5"><a class="btn" href="' + esc(d.open_url) + '">Open full page</a>'
            + '<button type="button" class="btn" data-s="close">Close</button></div>'
            + '</div>';

        body.onclick = async (e) => {
            const b = e.target.closest('[data-s]');
            if (!b) return;
            const act = b.dataset.s;
            if (act === 'close') { dlg.close(); return; }
            b.disabled = true;
            try {
                if (act === 'done') { dlg.close(); await markDone(card, false); }
                else if (act === 'pay') { dlg.close(); api().openPay(card); }
                else if (act === 'snooze') { dlg.close(); await snooze(card); }
                else if (act === 'stage') { dlg.close(); await setStage(card, b.dataset.stage); }
                else if (act === 'remind') {
                    const f = document.createElement('form');
                    f.method = 'POST'; f.action = d.remind_url;
                    f.innerHTML = '<input type="hidden" name="_token" value="' + esc(document.querySelector('meta[name="csrf-token"]')?.content) + '">';
                    document.body.appendChild(f); f.submit();
                } else if (act === 'note') {
                    const note = body.querySelector('[data-note]').value.trim();
                    if (!note) { b.disabled = false; return; }
                    await api().send(d.note_url, 'PATCH', { note });
                    dlg.close(); api().toast('Note saved: ' + d.title);
                } else if (act === 'time') {
                    await api().send(d.time_url, 'POST', { hours: body.querySelector('[data-hours]').value, date: dlg.dataset.today });
                    dlg.close(); api().toast('Time logged: ' + d.title);
                } else if (act === 'reassign') {
                    const sel = body.querySelector('[data-assignee]');
                    await api().send(dlg.dataset.reassignUrl, 'PATCH', { task_id: d.id, assigned_to: sel.value });
                    dlg.close(); api().toast('Reassigned to ' + sel.options[sel.selectedIndex].text + ': ' + d.title);
                    setTimeout(() => api().refresh(true), 400);
                }
            } catch (err) {
                b.disabled = false;
                api().toast('That didn’t work: ' + err.message);
            }
        };

        dlg.showModal();
        if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
    }

    // Tap a card on a phone → panel (instead of jumping to another page).
    document.addEventListener('click', (e) => {
        if (!phone.matches) return;
        const card = e.target.closest('[data-dash-item][data-sheet]');
        if (!card || e.target.closest('button, form, [data-dash-action]')) return;
        e.preventDefault();
        if (Date.now() < suppressClick) return;
        openSheet(card);
    });
    document.addEventListener('click', (e) => {
        const dlg = document.getElementById('dash-sheet');
        if (dlg && e.target === dlg) dlg.close();                      // tap on the dimmed area closes
    });

    // ------------------------------------------------------------ swipe on cards
    let sw = null;
    document.addEventListener('touchstart', (e) => {
        if (!phone.matches || e.touches.length !== 1) return;
        const card = e.target.closest('[data-dash-item][data-sheet]');
        if (!card || e.target.closest('select, textarea, input')) return;
        const t = e.touches[0];
        sw = { card, x: t.clientX, y: t.clientY, dx: 0, on: false, dead: false,
               face: card.querySelector('[data-swipe-face]'), under: card.querySelector('[data-swipe-under]') };
    }, { passive: true });

    document.addEventListener('touchmove', (e) => {
        if (!sw || sw.dead) return;
        const t = e.touches[0];
        const dx = t.clientX - sw.x, dy = t.clientY - sw.y;
        if (!sw.on) {
            if (Math.abs(dy) > 12 && Math.abs(dy) > Math.abs(dx)) { sw.dead = true; return; }
            if (Math.abs(dx) > 14 && Math.abs(dx) > Math.abs(dy) * 1.4) { sw.on = true; api().touched(); }
            else return;
        }
        e.preventDefault();
        sw.dx = dx;
        sw.face.style.transition = 'none';
        sw.face.style.transform = 'translateX(' + dx + 'px)';
        sw.under.style.opacity = '1';
        sw.under.style.background = dx > 0 ? '#059669' : '#d97706';
        sw.under.querySelector('[data-swipe-left-label]').style.visibility = dx > 0 ? 'visible' : 'hidden';
        sw.under.querySelector('[data-swipe-right-label]').style.visibility = dx < 0 ? 'visible' : 'hidden';
        const past = Math.abs(dx) > Math.min(110, sw.card.offsetWidth * 0.33);
        if (past && !sw.buzzed) { vibrate(8); sw.buzzed = true; }
        if (!past) sw.buzzed = false;
    }, { passive: false });

    document.addEventListener('touchend', () => {
        if (!sw) return;
        const s = sw; sw = null;
        if (!s.on) return;
        suppressClick = Date.now() + 400;
        const past = Math.abs(s.dx) > Math.min(110, s.card.offsetWidth * 0.33);
        const reset = () => {
            s.face.style.transition = 'transform .2s ease';
            s.face.style.transform = '';
            setTimeout(() => { s.under.style.opacity = '0'; }, 200);
        };
        if (!past) { reset(); return; }
        const d = data(s.card);
        const willLeave = s.dx < 0 || (d.type === 'task' && d.complete_url && !d.pay);
        if (willLeave) {
            s.face.style.transition = 'transform .18s ease';
            s.face.style.transform = 'translateX(' + (s.dx > 0 ? '110%' : '-110%') + ')';
        } else {
            reset();
        }
        const done = s.dx > 0 ? markDone(s.card, true) : snooze(s.card);
        Promise.resolve(done).finally(() => { if (s.card.isConnected) setTimeout(reset, 250); });
    });

    // ------------------------------------------------------------ pull to refresh
    let pull = null;
    const atTop = () => Math.max(document.body.scrollTop, (document.scrollingElement || document.documentElement).scrollTop) <= 0;
    function pullEl() {
        let el = document.getElementById('dash-pull');
        if (!el) {
            el = document.createElement('div');
            el.id = 'dash-pull';
            el.setAttribute('aria-hidden', 'true');
            el.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.4" stroke-linecap="round"><path d="M21 12a9 9 0 11-3-6.7L21 8M21 3v5h-5"/></svg>';
            document.body.appendChild(el);
        }
        return el;
    }
    document.addEventListener('touchstart', (e) => {
        if (!phone.matches || !atTop() || document.querySelector('dialog[open]')) return;
        if (!document.querySelector('[data-dashboard-layout="board"]')) return;
        pull = { y: e.touches[0].clientY, dy: 0 };
    }, { passive: true });
    document.addEventListener('touchmove', (e) => {
        if (!pull || (sw && sw.on)) return;
        pull.dy = e.touches[0].clientY - pull.y;
        if (pull.dy <= 0) return;
        const el = pullEl();
        el.style.transition = 'none';
        el.style.transform = 'translate(-50%, ' + (Math.min(pull.dy, 120) * 0.6 - 60) + 'px) rotate(' + (pull.dy * 2) + 'deg)';
    }, { passive: true });
    document.addEventListener('touchend', async () => {
        if (!pull) return;
        const p = pull; pull = null;
        const el = document.getElementById('dash-pull');
        if (!el) return;
        el.style.transition = 'transform .2s';
        if (p.dy > 80) {
            el.style.transform = 'translate(-50%, 12px)';
            el.classList.add('spin');
            try { await api().refresh(true); } finally {
                el.classList.remove('spin');
                el.style.transform = 'translate(-50%, -80px)';
            }
        } else {
            el.style.transform = 'translate(-50%, -80px)';
        }
    });
})();
</script>
