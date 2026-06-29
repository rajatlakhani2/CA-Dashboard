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

    function colToWidth(col, container) {
        if (!container || !container.clientWidth) return null;
        var colW = container.clientWidth / 12;
        return Math.round(colW * col);
    }

    function migratePixelSizes(sizes) {
        if (!sizes || typeof sizes !== 'object') return {};
        var container = document.getElementById('executive-summary-sortable');
        var migrated = {};
        Object.keys(sizes).forEach(function (id) {
            var size = sizes[id] || {};
            if (size.w) {
                migrated[id] = { w: size.w, h: size.h };
            } else if (size.col) {
                migrated[id] = { w: colToWidth(size.col, container), h: size.h };
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

    function clearWidgetWidthClasses(widget) {
        COL_SPANS.forEach(function (col) {
            widget.classList.remove('exec-widget--col-' + col);
        });
    }

    function setWidgetWidth(widget, widthPx) {
        clearWidgetWidthClasses(widget);
        if (!widthPx) {
            widget.style.removeProperty('--exec-widget-w');
            widget.classList.remove('exec-widget--sized-width');
            return;
        }
        widget.style.setProperty('--exec-widget-w', Math.round(widthPx) + 'px');
        widget.classList.add('exec-widget--sized-width');
    }

    function setWidgetColSpan(widget, span) {
        clearWidgetWidthClasses(widget);
        widget.classList.remove('exec-widget--sized-width');
        widget.style.removeProperty('--exec-widget-w');
        if (span && span < 12) {
            widget.classList.add('exec-widget--col-' + span);
            widget.classList.add('exec-widget--sized-width');
        }
    }

    function applySavedSizes(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;

        var sizes = readStorage()[sizesKey] || {};
        container.querySelectorAll('.exec-widget').forEach(function (widget) {
            var id = widget.getAttribute('data-dashboard-widget');
            var size = sizes[id];
            if (!size) return;
            if (size.w) {
                setWidgetWidth(widget, size.w);
            } else if (size.col) {
                setWidgetColSpan(widget, size.col);
            }
            if (size.h) {
                var body = widget.querySelector('.exec-widget__body');
                if (body) {
                    body.style.setProperty('--exec-widget-h', size.h + 'px');
                    body.classList.add('exec-widget__body--sized');
                }
            }
        });
    }

    function applySavedColSpans(containerId) {
        applySavedSizes(containerId);
    }

    window.VouchexExecLayout = {
        readStorage: readStorage,
        writeStorage: writeStorage,
        storageKey: storageKey,
        sizesKey: sizesKey,
        migratePixelSizes: migratePixelSizes,
        applySavedSizes: applySavedSizes,
        setWidgetWidth: setWidgetWidth,
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
            animation: 220,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
            handle: '.dashboard-drag-handle',
            ghostClass: 'dashboard-widget-ghost',
            dragClass: 'dashboard-widget-drag',
            forceFallback: true,
            fallbackTolerance: 4,
            swapThreshold: 0.55,
            invertSwap: true,
            delay: 80,
            delayOnTouchOnly: true,
            touchStartThreshold: 4,
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

    function initKeyboardWidgetReorder(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;

        container.addEventListener('keydown', function (e) {
            if (!e.altKey || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) return;
            var handle = e.target.closest('.dashboard-drag-handle');
            if (!handle) return;
            var widget = handle.closest('[data-dashboard-widget]');
            if (!widget) return;
            e.preventDefault();
            var sibling = e.key === 'ArrowUp' ? widget.previousElementSibling : widget.nextElementSibling;
            if (!sibling || !sibling.hasAttribute('data-dashboard-widget')) return;
            if (e.key === 'ArrowUp') {
                container.insertBefore(widget, sibling);
            } else {
                container.insertBefore(sibling, widget);
            }
            var order = Array.from(container.querySelectorAll('[data-dashboard-widget]')).map(function (el) {
                return el.getAttribute('data-dashboard-widget');
            });
            scheduleSave(function (state) {
                state[containerId] = filterOrder(order);
            });
            refreshCalendar();
            handle.focus();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSortable('dashboard-overview-sortable');
        initSortable('dashboard-mission-sortable');
        initSortable('executive-summary-sortable');
        initKeyboardWidgetReorder('executive-summary-sortable');
        applySavedColSpans('executive-summary-sortable');
        initExecutiveCollapse();
    });

    window.addEventListener('load', function () {
        applySavedColSpans('executive-summary-sortable');
    });
})();
</script>
