<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Theme gallery — Kuhu Command Centre</title>
    @foreach($themes as $t)
    <link rel="stylesheet" href="{{ $t['font_url'] }}">
    @endforeach
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DM Sans', system-ui, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }
        .page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.25rem 3rem;
        }
        .page h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.35rem;
            color: #fff;
        }
        .page > p {
            color: #94a3b8;
            margin: 0 0 2rem;
            font-size: 0.95rem;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        .theme-card {
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: transform 0.2s, border-color 0.2s;
            cursor: default;
        }
        .theme-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255,255,255,0.15);
        }
        .theme-card__meta {
            padding: 1rem 1.1rem 0.75rem;
            background: rgba(255,255,255,0.06);
        }
        .theme-card__meta h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .badge {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            background: #334155;
            color: #e2e8f0;
        }
        .badge-gold { background: #b45309; color: #fff; }
        .badge-blue { background: #2563eb; color: #fff; }
        .theme-card__meta p {
            margin: 0.35rem 0 0;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .theme-card__meta .spec {
            margin-top: 0.5rem;
            font-size: 0.72rem;
            color: #64748b;
        }
        .preview {
            display: flex;
            min-height: 220px;
            font-size: var(--t-base, 15px);
            font-family: var(--t-font, sans-serif);
        }
        .preview-sidebar {
            width: 38%;
            min-width: 120px;
            padding: 0.85rem 0.65rem;
            background: linear-gradient(175deg, var(--t-sidebar) 0%, var(--t-sidebar-mid) 100%);
            color: #fff;
        }
        .preview-sidebar .logo {
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-align: center;
            margin-bottom: 0.85rem;
            line-height: 1.3;
            opacity: 0.95;
        }
        .preview-sidebar .nav-label {
            font-size: 0.55rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            opacity: 0.45;
            margin: 0.5rem 0 0.25rem 0.35rem;
        }
        .preview-sidebar .nav-item {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.35rem 0.5rem;
            border-radius: 8px;
            margin-bottom: 0.15rem;
            opacity: 0.65;
        }
        .preview-sidebar .nav-item.active {
            background: var(--t-accent);
            opacity: 1;
        }
        .preview-main {
            flex: 1;
            background: var(--t-bg);
            color: var(--t-text);
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .preview-header {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--t-text);
        }
        .preview-header span {
            display: block;
            font-size: 0.62rem;
            font-weight: 500;
            color: var(--t-muted);
            margin-top: 0.1rem;
        }
        .preview-kpis {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.4rem;
        }
        .kpi {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: var(--t-radius);
            padding: 0.45rem 0.5rem;
            border-top: 3px solid var(--t-accent);
        }
        .kpi .label {
            font-size: 0.55rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--t-muted);
        }
        .kpi .value {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--t-text);
            margin-top: 0.1rem;
        }
        .preview-btn {
            align-self: flex-start;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.35rem 0.65rem;
            border-radius: 8px;
            background: var(--t-accent);
            color: #fff;
            border: none;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 1.25rem;
            color: #93c5fd;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .back-link:hover { text-decoration: underline; }
        .footer-note {
            margin-top: 2rem;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            font-size: 0.85rem;
            color: #94a3b8;
        }
        .footer-note strong { color: #e2e8f0; }
    </style>
</head>
<body>
<div class="page">
    <a href="{{ route('settings.index') }}" class="back-link">← Back to Settings</a>
    <h1>Font & colour gallery</h1>
    <p>Five looks for your dashboard. Tell us which number you prefer and we'll apply it across login, sidebar, and main pages.</p>

    <div class="grid">
        @foreach($themes as $i => $t)
        <div class="theme-card" style="
            --t-font: '{{ $t['font'] }}', system-ui, sans-serif;
            --t-sidebar: {{ $t['sidebar'] }};
            --t-sidebar-mid: {{ $t['sidebar_mid'] }};
            --t-accent: {{ $t['accent'] }};
            --t-bg: {{ $t['bg'] }};
            --t-text: {{ $t['text'] }};
            --t-muted: {{ $t['muted'] }};
            --t-radius: {{ $t['radius'] }};
            --t-base: {{ $t['base_size'] }};
        ">
            <div class="theme-card__meta">
                <h2>
                    {{ $i + 1 }}. {{ $t['name'] }}
                    @if($t['badge'] === 'Current')
                    <span class="badge">{{ $t['badge'] }}</span>
                    @elseif($t['badge'] === 'Recommended')
                    <span class="badge badge-blue">{{ $t['badge'] }}</span>
                    @elseif($t['badge'])
                    <span class="badge badge-gold">{{ $t['badge'] }}</span>
                    @endif
                </h2>
                <p>{{ $t['vibe'] }}</p>
                <p class="spec">Font: {{ $t['font'] }} · Base {{ $t['base_size'] }} · Accent {{ $t['accent'] }}</p>
            </div>
            <div class="preview">
                <div class="preview-sidebar">
                    <div class="logo">KUHU<br>COMMAND</div>
                    <div class="nav-label">Compliance</div>
                    <div class="nav-item active">Dashboard</div>
                    <div class="nav-item">Clients</div>
                    <div class="nav-item">Reminders</div>
                </div>
                <div class="preview-main">
                    <div class="preview-header">
                        Good morning, Rajat
                        <span>Compliance & reminders workspace</span>
                    </div>
                    <div class="preview-kpis">
                        <div class="kpi">
                            <div class="label">Tasks due</div>
                            <div class="value">3</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Due today</div>
                            <div class="value">1</div>
                        </div>
                    </div>
                    <button type="button" class="preview-btn">+ Add task</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="footer-note">
        <strong>How to choose:</strong> Reply with the option number (1–5), or mix — e.g. <em>"Slate Pro font with Forest sidebar"</em>.
        We won't change invoice PDFs or layouts, only fonts and colours.
    </div>
</div>
</body>
</html>
