<script>

(function () {

    var userId = @json(auth()->id());

    var storageKey = 'vouchex_dashboard_layout_' + userId;

    var sizesKey = 'executive-summary-sizes';

    var COL_SPANS = [3, 4, 6, 8, 12];

    var MIN_H = 72;

    var MAX_H = 720;



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



    function colSpanClasses() {

        return COL_SPANS.map(function (s) { return 'exec-widget--col-' + s; });

    }



    function snapColSpan(cols) {

        var clamped = Math.max(3, Math.min(12, cols));

        var best = 12;

        var diff = 99;

        COL_SPANS.forEach(function (span) {

            var d = Math.abs(span - clamped);

            if (d < diff) {

                diff = d;

                best = span;

            }

        });

        return best;

    }



    function spanFromWidth(px, container) {

        if (!container || !container.clientWidth) return 12;

        var colW = container.clientWidth / 12;

        return snapColSpan(Math.round(px / colW));

    }



    function setColSpan(widget, span) {

        colSpanClasses().forEach(function (cls) { widget.classList.remove(cls); });

        widget.style.width = '';

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



        var col = size.col || null;

        if (!col && size.w) {

            col = spanFromWidth(size.w, container);

        }

        if (col) {

            setColSpan(widget, col);

        }



        if (size.h) {

            var h = Math.min(Math.max(MIN_H, size.h), MAX_H);

            setBodyHeight(body, h);

        }



        refreshCalendar();

    }



    function readWidgetSize(widget) {

        var body = widget.querySelector('.exec-widget__body');

        var col = readColSpan(widget);

        var h = null;

        if (body && body.classList.contains('exec-widget__body--sized')) {

            var stored = body.style.getPropertyValue('--exec-widget-h');

            h = stored ? parseInt(stored, 10) : body.offsetHeight;

        }

        return {

            col: col < 12 ? col : null,

            h: h,

        };

    }



    function saveWidgetSize(widgetId, size) {

        var state = readStorage();

        if (!state[sizesKey]) state[sizesKey] = {};

        state[sizesKey][widgetId] = size;

        writeStorage(state);

    }



    function resetWidgetSize(widget) {

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

            window.setTimeout(function () { window.calendar.updateSize(); }, 80);

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

                        var nextW = Math.min(maxW, Math.max(maxW * 0.25, startW + (ev.clientX - startX)));

                        setColSpan(widget, spanFromWidth(nextW, container));

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

                    setColSpan(widget, 12);

                    saveWidgetSize(id, readWidgetSize(widget));

                    refreshCalendar();

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



    document.addEventListener('DOMContentLoaded', initWidgetResize);

})();

</script>

