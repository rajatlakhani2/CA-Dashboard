{{-- Captures JS / fetch errors on the dashboard for easy copy-paste to support. --}}
<div id="dashboard-error-reporter" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="alertdialog" aria-labelledby="der-title">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-start gap-3">
            <div>
                <h3 id="der-title" class="text-lg font-bold text-gray-900">Technical issue detected</h3>
                <p class="text-xs text-gray-500 mt-1">Copy the report below and send it to support.</p>
            </div>
            <button type="button" id="der-close" class="text-gray-400 hover:text-gray-700 text-2xl leading-none" aria-label="Close">&times;</button>
        </div>
        <pre id="der-body" class="px-5 py-4 text-xs font-mono text-gray-800 overflow-auto flex-1 whitespace-pre-wrap break-words bg-gray-50 m-4 mt-0 rounded-xl border border-gray-200"></pre>
        <div class="px-5 py-4 border-t border-gray-100 flex flex-wrap gap-2 justify-end">
            <button type="button" id="der-copy" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Copy as Markdown</button>
            <button type="button" id="der-dismiss" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('dashboard-error-reporter');
    var bodyEl = document.getElementById('der-body');
    if (!modal || !bodyEl) {
        return;
    }

    var lastReport = '';

    function formatReport(payload) {
        var lines = [
            '## Dashboard error report',
            '- **Time:** ' + new Date().toISOString(),
            '- **Page:** ' + location.href,
            '- **Build:** ' + (@json($dashboardBuildId ?? 'unknown')),
            '- **Type:** ' + (payload.type || 'error'),
        ];
        if (payload.message) {
            lines.push('- **Message:** ' + payload.message);
        }
        if (payload.url) {
            lines.push('- **URL:** ' + payload.url);
        }
        if (payload.status) {
            lines.push('- **HTTP status:** ' + payload.status);
        }
        if (payload.source) {
            lines.push('- **Source:** ' + payload.source + ':' + (payload.line || '?'));
        }
        if (payload.stack) {
            lines.push('', '### Stack', '```', payload.stack, '```');
        }
        if (payload.body) {
            lines.push('', '### Response (truncated)', '```', payload.body, '```');
        }
        return lines.join('\n');
    }

    window.reportDashboardError = function (payload) {
        lastReport = formatReport(payload || {});
        bodyEl.textContent = lastReport;
        modal.classList.remove('hidden');
    };

    function closeModal() {
        modal.classList.add('hidden');
    }

    document.getElementById('der-close').addEventListener('click', closeModal);
    document.getElementById('der-dismiss').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.getElementById('der-copy').addEventListener('click', function () {
        var text = lastReport || bodyEl.textContent;
        var done = function () {
            alert('Copied to clipboard. Paste it in your support chat.');
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(function () {
                window.prompt('Copy this report:', text);
                done();
            });
        } else {
            window.prompt('Copy this report:', text);
            done();
        }
    });

    window.addEventListener('error', function (event) {
        window.reportDashboardError({
            type: 'javascript',
            message: event.message || 'Script error',
            source: event.filename || '',
            line: event.lineno,
            stack: event.error && event.error.stack ? event.error.stack : '',
        });
    });

    window.addEventListener('unhandledrejection', function (event) {
        var reason = event.reason;
        window.reportDashboardError({
            type: 'promise',
            message: reason && reason.message ? reason.message : String(reason),
            stack: reason && reason.stack ? reason.stack : '',
        });
    });

    var nativeFetch = window.fetch;
    if (nativeFetch) {
        window.fetch = function () {
            var args = arguments;
            var url = typeof args[0] === 'string' ? args[0] : (args[0] && args[0].url ? args[0].url : '');
            return nativeFetch.apply(this, args).then(function (response) {
                if (!response.ok && url.indexOf('/dashboard/deploy-probe') === -1) {
                    response.clone().text().then(function (text) {
                        window.reportDashboardError({
                            type: 'fetch',
                            url: url,
                            status: response.status,
                            message: response.statusText || 'Request failed',
                            body: (text || '').slice(0, 4000),
                        });
                    }).catch(function () {
                        window.reportDashboardError({
                            type: 'fetch',
                            url: url,
                            status: response.status,
                            message: response.statusText || 'Request failed',
                        });
                    });
                }
                return response;
            }).catch(function (err) {
                window.reportDashboardError({
                    type: 'fetch-network',
                    url: url,
                    message: err && err.message ? err.message : 'Network error',
                });
                throw err;
            });
        };
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-dashboard-report-issue]');
        if (btn) {
            e.preventDefault();
            if (lastReport) {
                window.reportDashboardError({ type: 'manual', message: 'Re-opening last captured error.' });
            } else {
                window.reportDashboardError({
                    type: 'manual',
                    message: 'No error captured yet. If you see a red Laravel page, use “Copy as Markdown” on that page. Otherwise reproduce the issue (click tab, calendar, etc.) and this dialog will fill automatically.',
                });
            }
        }
    }, true);
})();
</script>
