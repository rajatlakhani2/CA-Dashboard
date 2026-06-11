<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var userId = @json(auth()->id());
    var storageKey = 'vouchex_dashboard_layout_' + userId;

    function applyLayout(container, savedOrder) {
        if (!container || !savedOrder || !savedOrder.length) return;
        var widgets = {};
        container.querySelectorAll('[data-dashboard-widget]').forEach(function (el) {
            widgets[el.getAttribute('data-dashboard-widget')] = el;
        });
        savedOrder.forEach(function (id) {
            if (widgets[id]) container.appendChild(widgets[id]);
        });
    }

    function initSortable(containerId) {
        var container = document.getElementById(containerId);
        if (!container || typeof Sortable === 'undefined') return;

        var saved = null;
        try {
            saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
            if (saved && saved[containerId]) {
                applyLayout(container, saved[containerId]);
            }
        } catch (e) {}

        new Sortable(container, {
            animation: 180,
            handle: '.dashboard-drag-handle',
            ghostClass: 'dashboard-widget-ghost',
            dragClass: 'dashboard-widget-drag',
            onEnd: function () {
                var order = Array.from(container.querySelectorAll('[data-dashboard-widget]')).map(function (el) {
                    return el.getAttribute('data-dashboard-widget');
                });
                var all = {};
                try { all = JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch (e) {}
                all[containerId] = order;
                localStorage.setItem(storageKey, JSON.stringify(all));
            },
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSortable('dashboard-overview-sortable');
        initSortable('dashboard-mission-sortable');
    });
})();
</script>
