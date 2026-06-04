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

    function showDashboardTab(tab) {
        root.querySelectorAll('[data-dashboard-panel]').forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-dashboard-panel') !== tab);
        });
        root.querySelectorAll('[data-dashboard-tab]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-dashboard-tab') === tab);
        });
        if (tab === 'calendar') {
            setTimeout(function () {
                window.dispatchEvent(new Event('resize'));
                if (window.calendar) {
                    window.calendar.updateSize();
                    window.calendar.render();
                }
            }, 350);
        }
    }

    root.querySelectorAll('[data-dashboard-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showDashboardTab(btn.getAttribute('data-dashboard-tab'));
        });
    });

    var tab = root.getAttribute('data-initial-tab') || 'overview';
    if (location.hash === '#schedule') {
        tab = 'calendar';
    }
    if (location.hash === '#firm' && root.getAttribute('data-firm-tab') === '1') {
        tab = 'firm';
    }
    showDashboardTab(tab);
    window.showDashboardTab = showDashboardTab;
})();
</script>
