{{-- Auto-logout after inactivity (same behaviour as VouchEx): a countdown warning in the
     last minute, then sign-out. Activity in any open tab of this browser counts. --}}
@auth
@php $idleMinutes = \App\Support\SessionIdle::minutesFor(auth()->user()); @endphp
<div id="cd-idle" class="cd-idle" role="alertdialog" aria-modal="true" aria-labelledby="cd-idle-title" aria-describedby="cd-idle-text" hidden>
    <div class="cd-idle__card">
        <div class="cd-idle__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4M12 17h.01"/></svg>
        </div>
        <h3 id="cd-idle-title">Are you still there?</h3>
        <p id="cd-idle-text">For security, you will be signed out automatically because there has been no activity.</p>
        <div class="cd-idle__timer" aria-live="polite"><span id="cd-idle-count">60</span>s</div>
        <div class="cd-idle__actions">
            <button type="button" class="cd-idle__btn cd-idle__btn--ghost" id="cd-idle-out">Sign out now</button>
            <button type="button" class="cd-idle__btn cd-idle__btn--primary" id="cd-idle-stay">Stay signed in</button>
        </div>
    </div>
</div>
<form id="cd-idle-form" method="POST" action="{{ route('logout') }}" hidden>@csrf<input type="hidden" name="reason" value="idle"></form>
<script>
(function () {
    var LIMIT = {{ $idleMinutes }} * 60 * 1000;
    var WARN = Math.min(60000, Math.round(LIMIT / 3));
    var KEY = 'cadash.idle.last';
    var PING_EVERY = 60000;
    var box = document.getElementById('cd-idle');
    var countEl = document.getElementById('cd-idle-count');
    var token = document.querySelector('meta[name="csrf-token"]')?.content;
    var warning = false, done = false, lastPing = Date.now(), activeSincePing = false;

    function readLast() { try { return parseInt(localStorage.getItem(KEY) || '0', 10) || 0; } catch (e) { return 0; } }
    var memLast = Date.now();
    function writeLast(t) { memLast = t; try { localStorage.setItem(KEY, String(t)); } catch (e) {} }
    function last() { return Math.max(memLast, readLast()); }

    function ping() {
        lastPing = Date.now(); activeSincePing = false;
        fetch('{{ route('session.ping') }}', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { if (r.status === 401 || r.status === 419) location.href = '{{ route('login') }}'; })
            .catch(function () {});
    }
    function activity() {
        if (warning || done) return; // once the warning is up, only "Stay signed in" keeps the session
        writeLast(Date.now()); activeSincePing = true;
        if (Date.now() - lastPing > PING_EVERY) ping();
    }
    function hideWarning() { warning = false; box.hidden = true; document.documentElement.classList.remove('cd-idle-open'); }
    function signOut() {
        if (done) return; done = true;
        try { sessionStorage.setItem('cadash.idle.signedout', '1'); } catch (e) {}
        document.getElementById('cd-idle-form').submit();
    }

    ['pointerdown', 'keydown', 'wheel', 'touchstart', 'scroll', 'mousemove'].forEach(function (ev) {
        window.addEventListener(ev, activity, { passive: true, capture: true });
    });
    document.getElementById('cd-idle-stay').addEventListener('click', function () { hideWarning(); writeLast(Date.now()); ping(); });
    document.getElementById('cd-idle-out').addEventListener('click', signOut);
    // another tab was active: close our warning too
    window.addEventListener('storage', function (e) { if (e.key === KEY && warning && Date.now() - last() < LIMIT - WARN) hideWarning(); });

    writeLast(Date.now());
    setInterval(function () {
        if (done) return;
        var idle = Date.now() - last();
        if (idle >= LIMIT) { hideWarning(); signOut(); return; }
        if (idle >= LIMIT - WARN) {
            if (!warning) { warning = true; box.hidden = false; document.documentElement.classList.add('cd-idle-open'); document.getElementById('cd-idle-stay').focus(); }
            countEl.textContent = Math.max(0, Math.ceil((LIMIT - idle) / 1000));
        } else if (warning) {
            hideWarning();
        }
        if (activeSincePing && Date.now() - lastPing > PING_EVERY) ping();
    }, 1000);
})();
</script>
@endauth
