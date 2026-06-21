<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var userId = @json(auth()->id());
    var storageKey = 'vouchex_dashboard_layout_' + userId;
    var sortableKey = 'executive-summary-sortable';
    var collapsedKey = 'executive-summary-collapsed';
    var sizesKey = 'executive-summary-sizes';
    var saveTimer = null;

    function defaultOrder() {
        var container = document.getElementById('executive-summary-sortable');
        if (!container) return [];
        return Array.from(container.querySelectorAll('[data-dashboard-widget]')).map(function (el) {
            return el.getAttribute('data-dashboard-widget');
        });
    }

    function allowedWidgetIds() {
        var container = document.getElementById('executive-summary-sortable');
        if (!container) return [];
        try {
            var raw = container.getAttribute('data-allowed-widgets');
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function filterOrder(order) {
        var allowed = allowedWidgetIds();
        var container = document.getElementById('executive-summary-sortable');
        if (!container || !Array.isArray(order)) return defaultOrder();

        var inDom = {};
        container.querySelectorAll('[data-dashboard-widget]').forEach(function (el) {
            inDom[el.getAttribute('data-dashboard-widget')] = true;
        });

        var filtered = order.filter(function (id) {
            return inDom[id] && (!allowed.length || allowed.indexOf(id) !== -1);
        });

        Object.keys(inDom).forEach(function (id) {
            if (filtered.indexOf(id) === -1) filtered.push(id);
        });

        return filtered.length ? filtered : defaultOrder();
    }

    function pixelToColSpan(px) {
        if (px <= 300) return 3;
        if (px <= 400) return 4;
        if (px <= 600) return 6;
        if (px <= 800) return 8;
        return 12;
    }

    function migratePixelSizes(sizes) {
        if (!sizes || typeof sizes !== 'object') return {};
        var migrated = {};
        Object.keys(sizes).forEach(function (id) {
            var size = sizes[id] || {};
            if (size.w && !size.col) {
                migrated[id] = { col: pixelToColSpan(size.w), h: size.h };
            } else {
                migrated[id] = size;
            }
        });
        return migrated;
    }

    function getDefaultLayout() {
        return {
            'executive-summary-sortable': defaultOrder(),
            'executive-summary-collapsed': { 'exec-firm': true },
            'executive-summary-sizes': {},
        };
    }

    function loadLayout() {
        try {
            var raw = localStorage.getItem(storageKey);
            if (!raw) return getDefaultLayout();

            var parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') {
                throw new Error('invalid root');
            }

            if (!Array.isArray(parsed[sortableKey])) {
                console.warn('Vouchex: corrupt layout detected, resetting.');
                localStorage.removeItem(storageKey);
                return getDefaultLayout();
            }

            parsed[sortableKey] = filterOrder(parsed[sortableKey]);
            if (parsed[sizesKey]) {
                parsed[sizesKey] = migratePixelSizes(parsed[sizesKey]);
            }

            return parsed;
        } catch (e) {
            console.warn('Vouchex: layout parse error, resetting.', e);
            try { localStorage.removeItem(storageKey); } catch (err) {}
            return getDefaultLayout();
        }
    }

    function readStorage() {
        return loadLayout();
    }

    function notifyLayoutSaveFailed() {
        if (window.VouchexExecLayout && window.VouchexExecLayout._layoutSaveToastShown) {
            return;
        }
        if (window.VouchexExecLayout) {
            window.VouchexExecLayout._layoutSaveToastShown = true;
        }
        var el = document.getElementById('vouchex-layout-save-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'vouchex-layout-save-toast';
            el.setAttribute('role', 'status');
            el.style.cssText =
                'position:fixed;bottom:1.25rem;left:50%;transform:translateX(-50%);z-index:99990;' +
                'max-width:min(24rem,calc(100vw - 2rem));padding:0.75rem 1rem;border-radius:12px;' +
                'background:#1e293b;color:#f8fafc;font:600 13px system-ui,sans-serif;' +
                'box-shadow:0 12px 32px rgba(15,23,42,.35);';
            document.body.appendChild(el);
        }
        el.textContent = 'Could not save dashboard layout — storage may be full. Your view still works.';
        window.setTimeout(function () {
            if (el && el.parentNode) {
                el.parentNode.removeChild(el);
            }
            if (window.VouchexExecLayout) {
                window.VouchexExecLayout._layoutSaveToastShown = false;
            }
        }, 6000);
    }

    function writeStorage(all) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(all));
        } catch (e) {
            console.warn('Vouchex: could not save layout.', e);
            notifyLayoutSaveFailed();
        }
    }

    function scheduleSave(mutator) {
        if (saveTimer) window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(function () {
            var state = readStorage();
            mutator(state);
            writeStorage(state);
        }, 400);
    }

    function applyLayout(container, savedOrder) {
        if (!container || !savedOrder || !savedOrder.length) return;
        var widgets = {};
        container.querySelectorAll('[data-dashboard-widget]').forEach(function (el) {
            widgets[el.getAttribute('data-dashboard-widget')] = el;
        });
        filterOrder(savedOrder).forEach(function (id) {
            if (widgets[id]) container.appendChild(widgets[id]);
        });
    }

    function resetFinanceReveals(widget) {
        if (widget.getAttribute('data-dashboard-widget') !== 'exec-finance') return;
        var mask = widget.querySelector('.exec-finance-grid');
        if (mask && mask._x_dataStack && mask._x_dataStack[0] && typeof mask._x_dataStack[0].reset === 'function') {
            mask._x_dataStack[0].reset();
        }
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
        if (collapsed) {
            resetFinanceReveals(widget);
            widget.dispatchEvent(new CustomEvent('widget:collapsed', { bubbles: true }));
        }
    }

    function refreshCalendar() {
        if (window.calendar) {
            window.setTimeout(function () { window.calendar.updateSize(); }, 350);
        }
    }

    var COL_SPANS = [3, 4, 6, 8, 12];

    function setWidgetColSpan(widget, span) {
        COL_SPANS.forEach(function (col) {
            widget.classList.remove('exec-widget--col-' + col);
        });
        widget.style.width = '';
        widget.classList.remove('exec-widget--sized-width');
        if (span && span < 12) {
            widget.classList.add('exec-widget--col-' + span);
            widget.classList.add('exec-widget--sized-width');
        }
    }

    function applySavedColSpans(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;

        var sizes = readStorage()[sizesKey] || {};
        container.querySelectorAll('.exec-widget').forEach(function (widget) {
            var id = widget.getAttribute('data-dashboard-widget');
            var size = sizes[id];
            if (size && size.col) {
                setWidgetColSpan(widget, size.col);
            }
        });
    }

    window.VouchexExecLayout = {
        readStorage: readStorage,
        writeStorage: writeStorage,
        storageKey: storageKey,
        sizesKey: sizesKey,
        migratePixelSizes: migratePixelSizes,
        applySavedColSpans: applySavedColSpans,
        notifyLayoutSaveFailed: notifyLayoutSaveFailed,
    };

    function initExecutiveCollapse() {
        var container = document.getElementById('executive-summary-sortable');
        if (!container) return;

        var collapsed = readStorage()[collapsedKey] || {};

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
                scheduleSave(function (state) {
                    if (!state[collapsedKey]) state[collapsedKey] = {};
                    state[collapsedKey][id] = nextCollapsed;
                });
                if (!nextCollapsed) refreshCalendar();
            });
        });
    }

    function initSortable(containerId) {
        var container = document.getElementById(containerId);
        if (!container || typeof Sortable === 'undefined') return;

        var layout = readStorage();
        if (layout[containerId]) {
            applyLayout(container, layout[containerId]);
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
                scheduleSave(function (state) {
                    state[containerId] = filterOrder(order);
                });
                refreshCalendar();
            },
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSortable('dashboard-overview-sortable');
        initSortable('dashboard-mission-sortable');
        initSortable('executive-summary-sortable');
        applySavedColSpans('executive-summary-sortable');
        initExecutiveCollapse();
    });

    window.addEventListener('load', function () {
        applySavedColSpans('executive-summary-sortable');
    });
})();
</script>
