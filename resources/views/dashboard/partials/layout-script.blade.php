<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>

(function () {

    var userId = @json(auth()->id());

    var storageKey = 'vouchex_dashboard_layout_' + userId;

    var collapsedKey = 'executive-summary-collapsed';



    function readStorage() {

        try {

            return JSON.parse(localStorage.getItem(storageKey) || '{}');

        } catch (e) {

            return {};

        }

    }



    function writeStorage(all) {

        try {

            localStorage.setItem(storageKey, JSON.stringify(all));

        } catch (e) {}

    }



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



    function setWidgetCollapsed(widget, collapsed) {

        var body = widget.querySelector('.exec-widget__body');

        var layer = widget.querySelector('.exec-widget__resize-layer');

        var btn = widget.querySelector('.exec-widget__collapse');

        var icon = btn && btn.querySelector('.exec-widget__collapse-icon');

        widget.classList.toggle('exec-widget--collapsed', collapsed);

        if (body) body.hidden = collapsed;

        if (layer) layer.hidden = collapsed;

        if (btn) btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        if (icon) icon.textContent = collapsed ? '▶' : '▼';

    }



    function initExecutiveCollapse() {

        var container = document.getElementById('executive-summary-sortable');

        if (!container) return;



        var all = readStorage();

        var collapsed = all[collapsedKey] || {};



        container.querySelectorAll('.exec-widget').forEach(function (widget) {

            var id = widget.getAttribute('data-dashboard-widget');

            var btn = widget.querySelector('.exec-widget__collapse');

            if (!btn || !id) return;



            if (Object.prototype.hasOwnProperty.call(collapsed, id)) {

                setWidgetCollapsed(widget, !!collapsed[id]);

            } else if (widget.hasAttribute('data-default-collapsed')) {

                setWidgetCollapsed(widget, true);

            }



            btn.addEventListener('click', function (e) {

                e.preventDefault();

                e.stopPropagation();

                var nextCollapsed = !widget.classList.contains('exec-widget--collapsed');

                setWidgetCollapsed(widget, nextCollapsed);



                var state = readStorage();

                if (!state[collapsedKey]) state[collapsedKey] = {};

                state[collapsedKey][id] = nextCollapsed;

                writeStorage(state);



                if (!nextCollapsed && window.calendar) {

                    window.setTimeout(function () { window.calendar.updateSize(); }, 120);

                }

            });

        });

    }



    function initSortable(containerId) {

        var container = document.getElementById(containerId);

        if (!container || typeof Sortable === 'undefined') return;



        var all = readStorage();

        if (all[containerId]) {

            applyLayout(container, all[containerId]);

        }



        new Sortable(container, {

            animation: 180,

            handle: '.dashboard-drag-handle',

            ghostClass: 'dashboard-widget-ghost',

            dragClass: 'dashboard-widget-drag',

            onEnd: function () {

                var order = Array.from(container.querySelectorAll('[data-dashboard-widget]')).map(function (el) {

                    return el.getAttribute('data-dashboard-widget');

                });

                var state = readStorage();

                state[containerId] = order;

                writeStorage(state);



                if (window.calendar) {

                    window.setTimeout(function () { window.calendar.updateSize(); }, 120);

                }

            },

        });

    }



    document.addEventListener('DOMContentLoaded', function () {

        initSortable('dashboard-overview-sortable');

        initSortable('dashboard-mission-sortable');

        initSortable('executive-summary-sortable');

        initExecutiveCollapse();

    });

})();

</script>

