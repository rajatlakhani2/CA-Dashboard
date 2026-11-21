{{-- Cards fade up when a page opens, and as they scroll into view (VouchEx card motion). --}}
<script>
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var SEL = '.exec-kpi-card, .kpi-card, .glass-card, .invoice-card, .renewal-list-card, '
        + 'main .rounded-2xl.border, main .rounded-xl.border.bg-white, main .rounded-xl.shadow, main .rounded-xl.shadow-sm.bg-white';
    function eligible(el) {
        if (el.classList.contains('cursor-grab') || el.closest('table, form, dialog, [role="dialog"], .cd-idle, .fc, nav, header')) return false;
        // only the outermost card: skip cards that sit inside another card
        var p = el.parentElement && el.parentElement.closest(SEL);
        return !p;
    }
    function run() {
        var main = document.querySelector('main');
        if (!main) return;
        var cards = Array.prototype.filter.call(main.querySelectorAll(SEL), eligible).slice(0, 80);
        var vh = window.innerHeight, i = 0;
        var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
            var n = 0;
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                e.target.style.setProperty('--cd-i', n++ % 6);
                e.target.classList.remove('cd-card-wait');
                e.target.classList.add('cd-card-anim');
                io.unobserve(e.target);
            });
        }, { rootMargin: '0px 0px -6% 0px' }) : null;
        cards.forEach(function (el) {
            var r = el.getBoundingClientRect();
            if (r.top < vh) {
                el.style.setProperty('--cd-i', Math.min(i++, 10));
                el.classList.add('cd-card-anim');
            } else if (io) {
                el.classList.add('cd-card-wait');
                io.observe(el);
            }
            el.addEventListener('animationend', function () { el.classList.remove('cd-card-anim'); el.style.removeProperty('--cd-i'); }, { once: true });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();
</script>
