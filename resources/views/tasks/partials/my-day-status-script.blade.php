<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function toast(message) {
        if (window.DemoTourPlay?.toast) {
            window.DemoTourPlay.toast(message, 2200);
            return;
        }
        var el = document.getElementById('my-day-status-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'my-day-status-toast';
            el.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-50 max-w-sm px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold shadow-lg opacity-0 transition-opacity';
            document.body.appendChild(el);
        }
        el.textContent = message;
        el.style.opacity = '1';
        setTimeout(function () { el.style.opacity = '0'; }, 2200);
    }

    document.querySelectorAll('[data-my-day-status-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) return;
            btn.disabled = true;

            var card = form.closest('[data-my-day-task-card]');
            var statusEl = card?.querySelector('[data-my-day-status]');
            var statusLabel = form.getAttribute('data-status-label') || '';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('Status update failed');
                    return res.json();
                })
                .then(function (data) {
                    if (statusEl && statusLabel) statusEl.textContent = statusLabel;
                    if (statusLabel === @json(\App\Models\Task::STATUS_IN_PROGRESS)) form.remove();
                    card?.classList.add('demo-tour-flash-pulse');
                    setTimeout(function () { card?.classList.remove('demo-tour-flash-pulse'); }, 1200);
                    toast(data.message || 'Task status updated.');
                })
                .catch(function () { toast('Could not update task. Try again.'); })
                .finally(function () { btn.disabled = false; });
        });
    });
})();
</script>
