<script>
(function () {
    if (window.__dashboardTabsV2Init) {
        return;
    }
    window.__dashboardTabsV2Init = true;

    var root = document.getElementById('dashboard-tab-root');
    if (!root) {
        return;
    }

    function refreshCalendar() {
        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
            if (window.calendar) {
                window.calendar.updateSize();
                window.calendar.render();
            }
        }, 350);
    }

    function showDashboardTab(tab) {
        if (tab === 'calendar') {
            root.querySelectorAll('[data-dashboard-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-dashboard-panel') !== 'overview');
            });
            root.querySelectorAll('[data-dashboard-tab]').forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-dashboard-tab') === 'calendar');
            });
            setTimeout(function () {
                var el = document.getElementById('dashboard-schedule');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                refreshCalendar();
            }, 120);
            return;
        }

        root.querySelectorAll('[data-dashboard-panel]').forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-dashboard-panel') !== tab);
        });
        root.querySelectorAll('[data-dashboard-tab]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-dashboard-tab') === tab);
        });

        if (tab === 'overview') {
            refreshCalendar();
        }
    }

    root.querySelectorAll('[data-dashboard-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showDashboardTab(btn.getAttribute('data-dashboard-tab'));
        });
    });

    var tab = root.getAttribute('data-initial-tab') || 'overview';
    var urlTab = new URLSearchParams(location.search).get('tab');
    if (location.hash === '#schedule' || urlTab === 'calendar' || urlTab === 'schedule') {
        tab = 'calendar';
    }
    if (location.hash === '#firm' && root.getAttribute('data-firm-tab') === '1') {
        tab = 'firm';
    }
    showDashboardTab(tab);
    window.showDashboardTab = showDashboardTab;
})();
</script>
