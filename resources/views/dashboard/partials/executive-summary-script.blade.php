<script>
(function () {
    var sizesKey = 'executive-summary-sizes';
    var COL_SPANS = [3, 4, 6, 8, 12];
    var MIN_H = 56;
    var MAX_H = 960;
    var MIN_W = 220;

    function layoutApi() {
        return window.VouchexExecLayout || null;
    }

    function readStorage() {
        var api = layoutApi();
        if (api && api.readStorage) {
            return api.readStorage();
        }
        try {
            var userId = @json(auth()->id());
            return JSON.parse(localStorage.getItem('vouchex_dashboard_layout_' + userId) || '{}');
        } catch (e) {
            return {};
        }
    }

    function writeStorage(all) {
        var api = layoutApi();
        if (api && api.writeStorage) {
            api.writeStorage(all);
            return;
        }
        try {
            var userId = @json(auth()->id());
            localStorage.setItem('vouchex_dashboard_layout_' + userId, JSON.stringify(all));
        } catch (e) {}
    }

    function colSpanClasses() {
        return COL_SPANS.map(function (s) { return 'exec-widget--col-' + s; });
    }

    function clearWidthClasses(widget) {
        colSpanClasses().forEach(function (cls) { widget.classList.remove(cls); });
    }

    function setWidgetWidth(widget, widthPx) {
        var api = layoutApi();
        if (api && api.setWidgetWidth) {
            api.setWidgetWidth(widget, widthPx);
            return;
        }
        clearWidthClasses(widget);
        if (!widthPx) {
            widget.style.removeProperty('--exec-widget-w');
            widget.classList.remove('exec-widget--sized-width');
            return;
        }
        widget.style.setProperty('--exec-widget-w', Math.round(widthPx) + 'px');
        widget.classList.add('exec-widget--sized-width');
    }

    function setColSpan(widget, span) {
        clearWidthClasses(widget);
        widget.style.removeProperty('--exec-widget-w');
        widget.classList.remove('exec-widget--sized-width');
        if (span && span < 12) {
            widget.classList.add('exec-widget--col-' + span);
            widget.classList.add('exec-widget--sized-width');
        }
    }

    function readColSpan(widget) {
        for (var i = 0; i < COL_SPANS.length; i++) {
            if (widget.classList.contains('exec-widget--col-' + COL_SPANS[i])) {
                return COL_SPANS[i];
            }
        }
        return 12;
    }

    function setBodyHeight(body, h) {
        if (!body) return;
        body.style.setProperty('--exec-widget-h', h + 'px');
        body.classList.add('exec-widget__body--sized');
    }

    function clearBodyHeight(body) {
        if (!body) return;
        body.style.removeProperty('--exec-widget-h');
        body.classList.remove('exec-widget__body--sized');
    }

    function applyWidgetSize(widget, size, container) {
        if (!size) return;
        var body = widget.querySelector('.exec-widget__body');
        if (!body) return;

        if (size.w) {
            setWidgetWidth(widget, size.w);
        } else if (size.col) {
            setColSpan(widget, size.col);
        }

        if (size.h) {
            var h = Math.min(Math.max(MIN_H, size.h), MAX_H);
            setBodyHeight(body, h);
        }

        refreshCalendar();
    }

    function readWidgetSize(widget) {
        var body = widget.querySelector('.exec-widget__body');
        var w = widget.classList.contains('exec-widget--sized-width') ? widget.offsetWidth : null;
        var h = null;
        if (body && body.classList.contains('exec-widget__body--sized')) {
            var stored = body.style.getPropertyValue('--exec-widget-h');
            h = stored ? parseInt(stored, 10) : body.offsetHeight;
        }
        return { w: w, h: h };
    }

    function saveWidgetSize(widgetId, size) {
        var state = readStorage();
        if (!state[sizesKey]) state[sizesKey] = {};
        state[sizesKey][widgetId] = size;
        writeStorage(state);
    }

    function resetWidgetSize(widget) {
        setWidgetWidth(widget, null);
        setColSpan(widget, 12);
        clearBodyHeight(widget.querySelector('.exec-widget__body'));
        var id = widget.getAttribute('data-dashboard-widget');
        if (id) {
            var state = readStorage();
            if (state[sizesKey]) {
                delete state[sizesKey][id];
                writeStorage(state);
            }
        }
        refreshCalendar();
    }

    function refreshCalendar() {
        if (window.calendar) {
            window.setTimeout(function () { window.calendar.updateSize(); }, 350);
        }
    }

    function toggleResizeLayer(widget, visible) {
        var layer = widget.querySelector('.exec-widget__resize-layer');
        if (layer) layer.hidden = !visible;
    }

    function initWidgetResize() {
        var container = document.getElementById('executive-summary-sortable');
        if (!container) return;

        var saved = (readStorage()[sizesKey]) || {};

        container.querySelectorAll('.exec-widget').forEach(function (widget) {
            var id = widget.getAttribute('data-dashboard-widget');
            if (!id) return;

            if (saved[id]) {
                applyWidgetSize(widget, saved[id], container);
            }

            var body = widget.querySelector('.exec-widget__body');
            var layer = widget.querySelector('.exec-widget__resize-layer');
            if (!body || !layer) return;

            function startResize(mode, e) {
                e.preventDefault();
                e.stopPropagation();

                var startX = e.clientX;
                var startY = e.clientY;
                var startW = widget.offsetWidth;
                var startH = body.offsetHeight;
                var maxW = container.clientWidth;

                body.classList.add('exec-widget__body--resizing');
                widget.classList.add('exec-widget--resizing');

                function onMove(ev) {
                    if (mode === 'w' || mode === 'both') {
                        var nextW = Math.min(maxW, Math.max(MIN_W, startW + (ev.clientX - startX)));
                        setWidgetWidth(widget, nextW);
                    }

                    if (mode === 'h' || mode === 'both') {
                        var nextH = Math.min(MAX_H, Math.max(MIN_H, startH + (ev.clientY - startY)));
                        setBodyHeight(body, nextH);
                    }

                    refreshCalendar();
                }

                function onUp() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                    body.classList.remove('exec-widget__body--resizing');
                    widget.classList.remove('exec-widget--resizing');
                    saveWidgetSize(id, readWidgetSize(widget));
                    refreshCalendar();
                }

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            }

            var bottom = layer.querySelector('.exec-widget__resize--bottom');
            var right = layer.querySelector('.exec-widget__resize--right');
            var corner = layer.querySelector('.exec-widget__resize--corner');

            if (bottom) {
                bottom.addEventListener('mousedown', function (e) { startResize('h', e); });
                bottom.addEventListener('dblclick', function (e) {
                    e.preventDefault();
                    clearBodyHeight(body);
                    saveWidgetSize(id, readWidgetSize(widget));
                    refreshCalendar();
                });
            }

            if (right) {
                right.addEventListener('mousedown', function (e) { startResize('w', e); });
                right.addEventListener('dblclick', function (e) {
                    e.preventDefault();
                    resetWidgetSize(widget);
                });
            }

            if (corner) {
                corner.addEventListener('mousedown', function (e) { startResize('both', e); });
                corner.addEventListener('dblclick', function (e) {
                    e.preventDefault();
                    resetWidgetSize(widget);
                });
            }

            var collapseBtn = widget.querySelector('.exec-widget__collapse');
            if (collapseBtn) {
                collapseBtn.addEventListener('click', function () {
                    window.setTimeout(function () {
                        toggleResizeLayer(widget, !widget.classList.contains('exec-widget--collapsed'));
                    }, 0);
                });
            }
        });

        window.addEventListener('resize', function () { refreshCalendar(); });
    }

    var resizeBooted = false;

    function bootWidgetResize() {
        if (resizeBooted || !document.getElementById('executive-summary-sortable')) return;
        resizeBooted = true;
        initWidgetResize();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootWidgetResize);
        window.addEventListener('load', bootWidgetResize);
    } else {
        bootWidgetResize();
    }
})();
</script>
