<style>
    /* VouchEx-aligned palette — colours only, same CA layout */
    :root {
        --premium-navy: #0c1f4a;
        --premium-navy-soft: #0a1838;
        --premium-navy-muted: #061228;
        --premium-accent: #2563eb;
        --premium-accent-teal: #0d9488;
        --premium-accent-light: #60a5fa;
        --premium-surface: #ffffff;
        --premium-bg: #f8fafc;
        --premium-text: #0f172a;
        --premium-muted: #64748b;
        --premium-border: #e2e8f0;
        --premium-radius: 14px;
        --content-pad: clamp(1rem, 2.5vw, 2.25rem);
        --vx-shadow-card: 0 1px 0 rgba(255, 255, 255, 0.9) inset, 0 8px 24px -8px rgba(15, 23, 42, 0.1);
    }

    body.theme-modern,
    body.theme-executive,
    body.theme-dense,
    body.theme-glass {
        font-family: 'Plus Jakarta Sans', 'DM Sans', ui-sans-serif, system-ui, sans-serif;
        background: var(--premium-bg) !important;
        -webkit-font-smoothing: antialiased;
    }

    .font-display {
        font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
        letter-spacing: -0.01em;
    }

    .main-shell {
        background:
            radial-gradient(80% 50% at 100% 0%, rgba(37, 99, 235, 0.06) 0%, transparent 55%),
            radial-gradient(60% 40% at 0 100%, rgba(13, 148, 136, 0.05) 0%, transparent 50%),
            linear-gradient(165deg, #f1f5f9 0%, #f8fafc 45%, #eef2ff 100%) !important;
    }

    @media (min-width: 1024px) {
        .main-shell {
            width: calc(100vw - var(--sidebar-width, 16rem)) !important;
            max-width: calc(100vw - var(--sidebar-width, 16rem)) !important;
        }
    }

    main.main-content-pad {
        width: 100%;
        max-width: 100% !important;
        min-width: 0;
        padding-left: var(--content-pad) !important;
        padding-right: var(--content-pad) !important;
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .dashboard-shell {
        width: 100%;
        max-width: 100% !important;
        min-width: 0;
        margin: 0;
        box-sizing: border-box;
    }

    .dashboard-shell > * {
        max-width: 100%;
        min-width: 0;
    }

    .saas-workspace {
        width: 100%;
        border: 1px solid var(--premium-border);
        border-radius: calc(var(--premium-radius) + 2px);
        background: var(--premium-surface);
        box-shadow: var(--vx-shadow-card);
    }

    .saas-workspace__hero {
        background: linear-gradient(175deg, var(--premium-navy) 0%, var(--premium-navy-soft) 48%, var(--premium-navy-muted) 100%);
        padding: clamp(1.25rem, 3vw, 2rem) var(--content-pad);
    }

    .saas-workspace__badge {
        display: inline-flex;
        align-items: center;
        border-radius: 6px;
        padding: 0.2rem 0.55rem;
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .saas-workspace__badge--plan {
        background: rgba(37, 99, 235, 0.22);
        border-color: rgba(96, 165, 250, 0.35);
        color: #93c5fd;
    }

    .saas-workspace__title {
        font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
        font-size: clamp(1.5rem, 2.8vw, 2.125rem);
        font-weight: 600;
        line-height: 1.15;
        color: #fff;
        margin-top: 0.65rem;
    }

    .saas-workspace__meta {
        font-size: clamp(0.8125rem, 1.5vw, 0.9375rem);
        color: rgba(255, 255, 255, 0.72);
        margin-top: 0.35rem;
    }

    .saas-workspace__stat {
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.65rem 1rem;
        text-align: center;
        min-width: 5.5rem;
    }

    .saas-workspace__btn {
        border-radius: 11px;
        background: linear-gradient(180deg, #3b82f6 0%, #2563eb 45%, #1d4ed8 100%);
        color: #fff;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 0.65rem 1.1rem;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.45), 0 8px 20px rgba(37, 99, 235, 0.35);
    }

    .saas-workspace__btn:hover {
        transform: translateY(-1px);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.5), 0 12px 28px rgba(37, 99, 235, 0.45);
    }

    .saas-team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 10.5rem), 1fr));
        gap: 0.5rem;
        width: 100%;
    }

    .saas-team-card {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        border-radius: 12px;
        border: 1px solid var(--premium-border);
        background: var(--premium-surface);
        padding: 0.65rem 0.75rem;
        min-width: 0;
    }

    .saas-team-card--you {
        border-color: #93c5fd;
        box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.2);
    }

    .saas-team-card__avatar {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 10px;
        background: var(--premium-navy);
        color: #93c5fd;
        font-size: 0.6875rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mission-control__panel {
        width: 100%;
        border: 1px solid var(--premium-border);
        border-radius: var(--premium-radius);
        background: rgba(255, 255, 255, 0.62);
        backdrop-filter: blur(14px) saturate(1.15);
        padding: clamp(1rem, 2.5vw, 1.5rem);
        box-shadow: var(--vx-shadow-card);
    }

    .mission-control__eyebrow {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--premium-accent);
    }

    .mission-control__heading {
        font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
        font-size: clamp(1.25rem, 2.2vw, 1.75rem);
        font-weight: 600;
        color: var(--premium-text);
        margin-top: 0.25rem;
    }

    .mc-strip {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 7.5rem), 1fr));
        gap: clamp(0.4rem, 1vw, 0.65rem);
        width: 100%;
    }

    .mc-strip-item {
        border-radius: 12px;
        border: 1px solid var(--premium-border);
        background: rgba(255, 255, 255, 0.72);
        padding: clamp(0.65rem, 1.5vw, 0.85rem);
        text-decoration: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .mc-strip-item:hover {
        border-color: #93c5fd;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.12);
    }

    .mc-strip-item p:first-child {
        font-size: 0.625rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--premium-muted);
        line-height: 1.25;
    }

    .mc-strip-item p:last-child {
        font-size: clamp(1.25rem, 2.5vw, 1.65rem);
        font-weight: 700;
        color: var(--premium-navy);
        margin-top: 0.35rem;
        font-variant-numeric: tabular-nums;
    }

    .onboarding-premium {
        border: 1px solid var(--premium-border);
        background: linear-gradient(90deg, #f8fafc, #f1f5f9);
        border-radius: var(--premium-radius);
    }

    .onboarding-premium .h-full.bg-indigo-600 {
        background: linear-gradient(135deg, #2563eb, #0d9488) !important;
    }

    .glass-tab.active {
        color: #fff !important;
        background: linear-gradient(135deg, #2563eb, #0d9488) !important;
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25) !important;
    }

    .kpi-card {
        border-radius: var(--premium-radius);
        border: 1px solid rgba(226, 232, 240, 0.85);
        background: rgba(255, 255, 255, 0.62);
        backdrop-filter: blur(12px) saturate(1.1);
        box-shadow: var(--vx-shadow-card);
    }

    .kpi-card .kpi-value {
        font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
        font-size: clamp(1.75rem, 3vw, 2.35rem);
    }

    .dashboard-brand-icon {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
        flex-shrink: 0;
    }
    .dashboard-brand-icon__circle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #ffedd5 0%, #fef3c7 100%);
        border: 2px solid #fdba74;
        box-shadow: 0 4px 14px rgba(251, 146, 60, 0.18);
    }
    .dashboard-brand-icon__strip {
        display: flex;
        gap: 0.25rem;
        font-size: 1.125rem;
        line-height: 1;
    }
    @media (min-width: 640px) {
        .dashboard-brand-icon__circle {
            width: 5rem;
            height: 5rem;
        }
    }
</style>
