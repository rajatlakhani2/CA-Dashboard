@php
    $portalErrorPayload = $portalError ?? ($portal_error ?? null) ?? session('portal_error');
    if (! $portalErrorPayload && isset($errors) && $errors->any()) {
        $portalErrorPayload = app(\App\Support\PortalErrorPresenter::class)
            ->fromMessageBag($errors, request());
    }
    $portalErrorAutoOpen = ($autoOpen ?? false) || filled($portalErrorPayload);
@endphp

<div id="portal-error-modal"
    class="{{ $portalErrorAutoOpen ? '' : 'hidden' }} fixed inset-0 z-[250] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-[2px]"
    role="alertdialog"
    aria-labelledby="portal-error-title"
    aria-modal="true">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-start gap-3">
            <div class="flex items-start gap-3 min-w-0">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </span>
                <h3 id="portal-error-title" class="text-base font-bold text-slate-900 leading-snug pe-error-title">
                    {{ $portalErrorPayload['title'] ?? 'Something went wrong' }}
                </h3>
            </div>
            <button type="button" data-portal-error-close class="text-slate-400 hover:text-slate-700 text-2xl leading-none shrink-0" aria-label="Close">&times;</button>
        </div>

        <div class="px-5 py-4 space-y-3 overflow-y-auto text-sm pe-error-body">
            <div class="rounded-lg bg-sky-50 border border-sky-100 px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-sky-700">What you were doing</p>
                <p class="mt-1 text-slate-800 pe-error-action">{{ $portalErrorPayload['action'] ?? '' }}</p>
            </div>
            <div class="rounded-lg bg-rose-50 border border-rose-100 px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-rose-700">Problem</p>
                <p class="mt-1 text-slate-800 pe-error-problem">{{ $portalErrorPayload['problem'] ?? '' }}</p>
            </div>
            <div class="rounded-lg bg-teal-50 border border-teal-100 px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-teal-700">Technical detail</p>
                <p class="mt-1 text-slate-800 font-mono text-xs whitespace-pre-wrap break-words pe-error-technical">{{ $portalErrorPayload['technical'] ?? '' }}</p>
            </div>
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-amber-800">Why this happened</p>
                <p class="mt-1 text-slate-800 pe-error-why">{{ $portalErrorPayload['why'] ?? '' }}</p>
            </div>
            <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-indigo-700">What to do</p>
                <p class="mt-1 text-slate-800 pe-error-todo">{{ $portalErrorPayload['todo'] ?? '' }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600">Reference</p>
                <p class="mt-1 text-slate-700 font-mono text-xs pe-error-reference">{{ $portalErrorPayload['reference'] ?? '' }}</p>
            </div>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <button type="button" data-portal-error-copy class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Copy details
            </button>
            <button type="button" data-portal-error-close class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm">
                OK, I understand
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('portal-error-modal');
    if (!modal || window.__portalErrorModalInit) {
        return;
    }
    window.__portalErrorModalInit = true;

    var copyPayload = @json(app(\App\Support\PortalErrorPresenter::class)->toCopyText($portalErrorPayload ?? []));

    function setField(selector, value) {
        var el = modal.querySelector(selector);
        if (el) {
            el.textContent = value || '';
        }
    }

    window.showPortalError = function (payload) {
        if (!payload) {
            return;
        }
        setField('.pe-error-title', payload.title);
        setField('.pe-error-action', payload.action);
        setField('.pe-error-problem', payload.problem);
        setField('.pe-error-technical', payload.technical);
        setField('.pe-error-why', payload.why);
        setField('.pe-error-todo', payload.todo);
        setField('.pe-error-reference', payload.reference);
        copyPayload = [
            payload.title,
            'WHAT YOU WERE DOING: ' + (payload.action || ''),
            'PROBLEM: ' + (payload.problem || ''),
            'TECHNICAL DETAIL: ' + (payload.technical || ''),
            'WHY THIS HAPPENED: ' + (payload.why || ''),
            'WHAT TO DO: ' + (payload.todo || ''),
            'REFERENCE: ' + (payload.reference || ''),
        ].filter(Boolean).join('\n\n');
        modal.classList.remove('hidden');
    };

    function closeModal() {
        modal.classList.add('hidden');
    }

    modal.querySelectorAll('[data-portal-error-close]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    var copyBtn = modal.querySelector('[data-portal-error-copy]');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var text = copyPayload || '';
            var done = function () {
                copyBtn.textContent = 'Copied!';
                setTimeout(function () {
                    copyBtn.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy details';
                }, 1500);
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
    }

    @if($portalErrorPayload)
    window.showPortalError(@json($portalErrorPayload));
    @endif

    var nativeFetch = window.fetch;
    if (nativeFetch && !window.__portalErrorFetchHook) {
        window.__portalErrorFetchHook = true;
        window.fetch = function () {
            var args = arguments;
            return nativeFetch.apply(this, args).then(function (response) {
                if (!response.ok) {
                    var clone = response.clone();
                    clone.json().then(function (data) {
                        if (data && data.portal_error) {
                            window.showPortalError(data.portal_error);
                        }
                    }).catch(function () {});
                }
                return response;
            });
        };
    }
})();
</script>
