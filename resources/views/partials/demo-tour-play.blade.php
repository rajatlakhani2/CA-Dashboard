@if(!empty($demoTour['show']) || !empty($demoTour['isDemo']))
<style>
    .demo-tour-cursor {
        position: fixed;
        z-index: 220;
        width: 28px;
        height: 28px;
        margin: -4px 0 0 -4px;
        pointer-events: none;
        opacity: 0;
        transform: translate(-12px, -12px) scale(0.85);
        transition: left 0.45s cubic-bezier(.4,0,.2,1), top 0.45s cubic-bezier(.4,0,.2,1), opacity 0.2s, transform 0.2s;
    }
    .demo-tour-cursor.visible { opacity: 1; transform: translate(0, 0) scale(1); }
    .demo-tour-cursor svg { filter: drop-shadow(0 2px 6px rgba(0,0,0,0.35)); }
    .demo-tour-click-ring {
        position: fixed;
        z-index: 219;
        width: 36px;
        height: 36px;
        margin: -18px 0 0 -18px;
        border-radius: 50%;
        border: 2px solid rgba(99, 102, 241, 0.85);
        pointer-events: none;
        animation: demo-tour-ring 0.55s ease-out forwards;
    }
    @keyframes demo-tour-ring {
        from { transform: scale(0.4); opacity: 1; }
        to { transform: scale(1.8); opacity: 0; }
    }
    .demo-tour-live-badge {
        position: fixed;
        top: 4.5rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 218;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.85rem;
        border-radius: 9999px;
        background: rgba(15, 23, 42, 0.92);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.25);
    }
    .demo-tour-live-badge .pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #34d399;
        animation: demo-tour-pulse 1.2s ease-in-out infinite;
    }
    @keyframes demo-tour-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.45; transform: scale(0.85); }
    }
    .demo-tour-toast {
        position: fixed;
        bottom: 5.5rem;
        left: 50%;
        transform: translateX(-50%) translateY(12px);
        z-index: 218;
        max-width: 22rem;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18);
        font-size: 0.875rem;
        font-weight: 600;
        color: #0f172a;
        opacity: 0;
        transition: opacity 0.25s, transform 0.25s;
    }
    .demo-tour-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    .demo-tour-flash-pulse { animation: demo-tour-flash 1.1s ease-in-out 2; }
    @keyframes demo-tour-flash {
        0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        50% { box-shadow: 0 0 0 6px rgba(99, 102, 241, 0.35); }
    }
    .demo-tour-drag-ghost {
        position: fixed;
        z-index: 217;
        width: 11rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.65rem;
        background: #fff;
        border: 2px solid #6366f1;
        box-shadow: 0 12px 28px rgba(79, 70, 229, 0.25);
        font-size: 0.75rem;
        font-weight: 700;
        color: #1e293b;
        pointer-events: none;
        transition: left 0.9s cubic-bezier(.4,0,.2,1), top 0.9s cubic-bezier(.4,0,.2,1), opacity 0.3s;
    }
</style>
<div id="demo-tour-cursor" class="demo-tour-cursor" aria-hidden="true">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M5 3l14 9-6.5 1.5L11 21l-1.5-6L5 3z" fill="#fff" stroke="#1e293b" stroke-width="1.5" stroke-linejoin="round"/></svg>
</div>
<div id="demo-tour-live-badge" class="demo-tour-live-badge hidden" aria-live="polite">
    <span class="pulse"></span> Live demo playing…
</div>
<div id="demo-tour-toast" class="demo-tour-toast" role="status"></div>

<script>
window.DemoTourPlay = (function () {
    const ctx = @json([
        'clientName' => $demoTour['clientName'] ?? 'Acme Corp',
        'staffName' => $demoTour['staffName'] ?? 'Neha Kapoor',
    ]);

    function sleep(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function playDoneKey(index) {
        return 'vouchex_demo_play_done_' + index;
    }

    function isPlayDone(index) {
        return sessionStorage.getItem(playDoneKey(index)) === '1';
    }

    function markPlayDone(index) {
        sessionStorage.setItem(playDoneKey(index), '1');
    }

    function clearAllPlayDone() {
        Object.keys(sessionStorage).forEach(function (key) {
            if (key.indexOf('vouchex_demo_play_done_') === 0) {
                sessionStorage.removeItem(key);
            }
        });
    }

    function showLiveBadge(show) {
        const badge = document.getElementById('demo-tour-live-badge');
        if (!badge) return;
        badge.classList.toggle('hidden', !show);
    }

    function toast(message, ms) {
        const el = document.getElementById('demo-tour-toast');
        if (!el) return;
        el.textContent = message;
        el.classList.add('show');
        setTimeout(function () { el.classList.remove('show'); }, ms || 2800);
    }

    function clickRing(x, y) {
        const ring = document.createElement('div');
        ring.className = 'demo-tour-click-ring';
        ring.style.left = x + 'px';
        ring.style.top = y + 'px';
        document.body.appendChild(ring);
        setTimeout(function () { ring.remove(); }, 600);
    }

    async function moveCursorTo(x, y) {
        const cursor = document.getElementById('demo-tour-cursor');
        if (!cursor) return;
        cursor.style.left = x + 'px';
        cursor.style.top = y + 'px';
        cursor.classList.add('visible');
        await sleep(420);
    }

    async function cursorClick(el) {
        if (!el) return;
        el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        await sleep(280);
        const rect = el.getBoundingClientRect();
        const x = rect.left + rect.width / 2;
        const y = rect.top + rect.height / 2;
        await moveCursorTo(x, y);
        clickRing(x, y);
        el.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
        if (el.tagName === 'BUTTON' && el.type === 'submit') {
            el.closest('form')?.requestSubmit?.(el);
        }
        await sleep(320);
    }

    async function typeInto(input, text, speed) {
        if (!input) return;
        input.focus();
        for (let i = 0; i <= text.length; i++) {
            input.value = text.slice(0, i);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await sleep(speed || 38);
        }
    }

    function dismissBlockers() {
        document.querySelectorAll('[aria-labelledby="modal-title"] button').forEach(function (btn) {
            if ((btn.textContent || '').indexOf('Get Started') !== -1) btn.click();
        });
        document.getElementById('dateClickModal')?.classList.add('hidden');
    }

    function tomorrowDateStr() {
        const d = new Date();
        d.setDate(d.getDate() + 1);
        return d.toISOString().slice(0, 10);
    }

    const registry = {
        'dismiss-blockers': async function () {
            dismissBlockers();
            await sleep(200);
        },

        'pulse-mission-control': async function () {
            dismissBlockers();
            document.querySelectorAll('[data-demo-tour="mission-control"] a, [data-demo-tour="mission-control"] [class*="rounded"]').forEach(function (el, i) {
                if (i < 6) {
                    setTimeout(function () { el.classList.add('demo-tour-flash-pulse'); }, i * 120);
                    setTimeout(function () { el.classList.remove('demo-tour-flash-pulse'); }, 2200 + i * 120);
                }
            });
            await sleep(1600);
        },

        'my-day-start': async function () {
            const startBtn = Array.from(document.querySelectorAll('[data-my-day-status-form] button[type="submit"]')).find(function (btn) {
                return (btn.textContent || '').indexOf('Start') !== -1;
            }) || document.querySelector('.max-w-lg.mx-auto button[type="submit"]');
            const card = startBtn?.closest('[data-my-day-task-card]') || document.querySelector('.max-w-lg.mx-auto .bg-white.rounded-xl.border');
            if (startBtn && (startBtn.textContent || '').indexOf('Start') !== -1) {
                await cursorClick(startBtn);
                toast('Task moved to In Progress — same list from the morning WhatsApp.');
                await sleep(1800);
                return;
            }
            if (card) {
                card.classList.add('demo-tour-flash-pulse');
                toast('Tasks from the morning WhatsApp appear here for action.');
                await sleep(1400);
                card.classList.remove('demo-tour-flash-pulse');
            }
        },

        'client-show-work': async function () {
            const workTab = Array.from(document.querySelectorAll('button')).find(function (b) {
                return (b.textContent || '').trim() === 'Work';
            });
            if (workTab) await cursorClick(workTab);
            const taskRow = document.querySelector('[data-demo-tour="client-360"] li, [data-demo-tour="client-360"] .border-b');
            if (taskRow) {
                taskRow.classList.add('demo-tour-flash-pulse');
                await sleep(1200);
                taskRow.classList.remove('demo-tour-flash-pulse');
            }
        },

        'calendar-day-click': async function () {
            const schedule = document.getElementById('dashboard-schedule');
            if (schedule) {
                schedule.scrollIntoView({ behavior: 'smooth', block: 'start' });
                await sleep(500);
            }
            if (window.calendar) {
                window.calendar.updateSize();
            }
            const dateStr = tomorrowDateStr();
            let cell = document.querySelector('.fc-daygrid-day[data-date="' + dateStr + '"]');
            if (!cell && window.calendar) {
                window.calendar.gotoDate(dateStr);
                await sleep(500);
                cell = document.querySelector('.fc-daygrid-day[data-date="' + dateStr + '"]');
            }
            if (cell) {
                await cursorClick(cell);
            } else {
                document.getElementById('selectedDateText').innerText = dateStr;
                const addBtn = document.getElementById('btnAddTask');
                if (addBtn) {
                    addBtn.href = addBtn.href.split('?')[0] + '?due_date=' + dateStr + '&demo_tour=1';
                }
                document.getElementById('dateClickModal')?.classList.remove('hidden');
            }
            return { spotlight: '#dateClickModal' };
        },

        'calendar-after-create': async function () {
            if (window.calendar) {
                window.calendar.updateSize();
                window.calendar.render();
            }
            toast('✓ Task saved — new dot added to the calendar.');
            await sleep(900);
        },

        'task-create-live': async function (meta) {
            document.getElementById('dateClickModal')?.classList.add('hidden');
            const form = document.querySelector('[data-demo-tour="task-create-form"]');
            const title = 'Board meeting prep — ' + ctx.clientName;
            const titleInput = document.getElementById('title');
            await typeInto(titleInput, title, 58);
            await sleep(300);

            const clientChip = Array.from(document.querySelectorAll('.chip-btn')).find(function (btn) {
                return (btn.textContent || '').indexOf(ctx.clientName) !== -1;
            });
            if (clientChip) await cursorClick(clientChip);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'demo_tour';
            hidden.value = '1';
            form?.appendChild(hidden);

            const stepHidden = document.createElement('input');
            stepHidden.type = 'hidden';
            stepHidden.name = 'demo_tour_step';
            stepHidden.value = String((meta?.stepIndex ?? 0) + 1);
            form?.appendChild(stepHidden);

            await sleep(500);
            await sleep(400);
            if (form) {
                sessionStorage.setItem('vouchex_demo_tour_step', String((meta?.stepIndex ?? 0) + 1));
                sessionStorage.setItem('vouchex_demo_tour_active', '1');
                form.requestSubmit();
                return { reload: true };
            }
        },

        'workload-drag-demo': async function () {
            const cards = document.querySelectorAll('[data-demo-tour="workload-board"] [draggable="true"]');
            const source = cards[0];
            const columns = document.querySelectorAll('[data-demo-tour="workload-board"] [data-assignee-id]');
            const targetCol = columns.length > 1 ? columns[columns.length - 1] : null;
            if (!source || !targetCol) {
                toast('Drag a task card to rebalance workload across the team.');
                await sleep(1200);
                return;
            }
            const s = source.getBoundingClientRect();
            const t = targetCol.getBoundingClientRect();
            const ghost = document.createElement('div');
            ghost.className = 'demo-tour-drag-ghost';
            ghost.textContent = (source.textContent || 'Task').trim().slice(0, 48);
            ghost.style.left = (s.left + 8) + 'px';
            ghost.style.top = (s.top + 8) + 'px';
            document.body.appendChild(ghost);
            await sleep(200);
            ghost.style.left = (t.left + 24) + 'px';
            ghost.style.top = (t.top + 48) + 'px';
            targetCol.classList.add('demo-tour-flash-pulse');
            await sleep(950);
            ghost.style.opacity = '0';
            await sleep(300);
            ghost.remove();
            targetCol.classList.remove('demo-tour-flash-pulse');
            toast('↔ Drag tasks between columns to reassign instantly.');
        },

        'quick-search-demo': async function () {
            if (typeof toggleCommandPalette === 'function') {
                toggleCommandPalette();
            } else {
                document.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true, bubbles: true }));
            }
            await sleep(350);
            const input = document.getElementById('command-palette-input');
            if (input) {
                await typeInto(input, 'Acme', 82);
                await sleep(700);
            }
            return { spotlight: '#command-palette-modal' };
        },

        'invoice-send-demo': async function () {
            const emailBtn = document.querySelector('[data-demo-tour="invoice-send-actions"] button[type="submit"]');
            if (emailBtn) {
                const form = emailBtn.closest('form');
                if (form) {
                    form.setAttribute('onsubmit', 'event.preventDefault(); return false;');
                }
                await cursorClick(emailBtn);
                toast('📧 Invoice emailed to client (demo preview)');
            }
        },

        'renewals-pulse': async function () {
            const row = document.querySelector('[data-demo-tour="renewals-view"] .bg-white, [data-demo-tour="renewals-view"] table tbody tr');
            if (row) {
                row.classList.add('demo-tour-flash-pulse');
                await sleep(1300);
                row.classList.remove('demo-tour-flash-pulse');
            }
        },

        'unbilled-select-demo': async function () {
            const checkbox = document.querySelector('[data-demo-tour="unbilled-queue"] input[type="checkbox"]');
            if (checkbox) {
                await cursorClick(checkbox);
                toast('Select completed work, then generate invoices in one batch.');
            }
        },
    };

    return {
        ctx: ctx,
        clearAllPlayDone: clearAllPlayDone,
        isPlayDone: isPlayDone,
        toast: toast,
        async run(playName, stepIndex, meta) {
            if (!playName || isPlayDone(stepIndex)) {
                return {};
            }
            const fn = registry[playName];
            if (!fn) return {};
            showLiveBadge(true);
            let result = {};
            try {
                result = (await fn(Object.assign({ stepIndex: stepIndex }, meta || {}))) || {};
                if (!result.reload) {
                    markPlayDone(stepIndex);
                }
            } finally {
                if (!result.reload) {
                    showLiveBadge(false);
                }
            }
            return result;
        },
    };
})();
</script>
@endif
