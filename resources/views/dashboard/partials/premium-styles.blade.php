<style>
    /* Premium Command Centre — fluid width, navy + brass palette */
    :root {
        --premium-navy: #0c1929;
        --premium-navy-soft: #152238;
        --premium-navy-muted: #1e3352;
        --premium-brass: #b8956a;
        --premium-brass-light: #d4bc94;
        --premium-surface: #ffffff;
        --premium-bg: #e8ecf1;
        --premium-text: #0f172a;
        --premium-muted: #5c6b7a;
        --premium-border: #d8dee6;
        --premium-radius: 14px;
        --content-pad: clamp(1rem, 2.5vw, 2.25rem);
    }

    body.theme-modern,
    body.theme-executive,
    body.theme-dense,
    body.theme-glass {
        font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
        background: var(--premium-bg) !important;
        -webkit-font-smoothing: antialiased;
    }

    .font-display {
        font-family: 'Source Serif 4', Georgia, 'Times New Roman', serif;
        letter-spacing: -0.02em;
    }

    .main-shell {
        background: var(--premium-bg) !important;
    }

    main.main-content-pad {
        width: 100%;
        max-width: none !important;
        padding-left: var(--content-pad) !important;
        padding-right: var(--content-pad) !important;
        box-sizing: border-box;
    }

    .dashboard-shell {
        width: 100%;
        max-width: none !important;
        margin: 0;
        box-sizing: border-box;
    }

    .saas-workspace {
        width: 100%;
        border: 1px solid var(--premium-border);
        border-radius: calc(var(--premium-radius) + 2px);
        background: var(--premium-surface);
        box-shadow: 0 1px 2px rgba(12, 25, 41, 0.06), 0 12px 40px -12px rgba(12, 25, 41, 0.12);
    }

    .saas-workspace__hero {
        background: linear-gradient(135deg, var(--premium-navy) 0%, var(--premium-navy-soft) 48%, var(--premium-navy-muted) 100%);
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
        background: rgba(184, 149, 106, 0.22);
        border-color: rgba(212, 188, 148, 0.35);
        color: var(--premium-brass-light);
    }

    .saas-workspace__title {
        font-family: 'Source Serif 4', Georgia, serif;
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
        border-radius: 12px;
        background: var(--premium-brass);
        color: var(--premium-navy);
        font-weight: 700;
        font-size: 0.75rem;
        padding: 0.65rem 1.1rem;
        transition: background 0.2s, transform 0.2s;
    }

    .saas-workspace__btn:hover {
        background: var(--premium-brass-light);
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
        border-color: var(--premium-brass);
        box-shadow: 0 0 0 1px rgba(184, 149, 106, 0.35);
    }

    .saas-team-card__avatar {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 10px;
        background: var(--premium-navy);
        color: var(--premium-brass-light);
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
        background: var(--premium-surface);
        padding: clamp(1rem, 2.5vw, 1.5rem);
        box-shadow: 0 1px 2px rgba(12, 25, 41, 0.04);
    }

    .mission-control__eyebrow {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--premium-brass);
    }

    .mission-control__heading {
        font-family: 'Source Serif 4', Georgia, serif;
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
        background: linear-gradient(180deg, #fafbfc 0%, #f4f6f9 100%);
        padding: clamp(0.65rem, 1.5vw, 0.85rem);
        text-decoration: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .mc-strip-item:hover {
        border-color: var(--premium-brass);
        box-shadow: 0 4px 14px rgba(12, 25, 41, 0.08);
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
        background: linear-gradient(90deg, #fafbfc, #f6f8fb);
        border-radius: var(--premium-radius);
    }

    .onboarding-premium .h-full.bg-indigo-600 {
        background: linear-gradient(90deg, var(--premium-navy-soft), var(--premium-navy-muted)) !important;
    }

    .glass-tab.active {
        color: #fff !important;
        background: linear-gradient(135deg, var(--premium-navy) 0%, var(--premium-navy-muted) 100%) !important;
        box-shadow: 0 2px 8px rgba(12, 25, 41, 0.2) !important;
    }

    .kpi-card {
        border-radius: var(--premium-radius);
        border: 1px solid var(--premium-border);
    }

    .kpi-card .kpi-value {
        font-family: 'Source Serif 4', Georgia, serif;
        font-size: clamp(1.75rem, 3vw, 2.35rem);
    }
</style>
