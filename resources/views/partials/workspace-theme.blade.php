@php
    $theme = $themePreset ?? \App\Support\ThemePreset::forWorkspaceType();
@endphp
<link rel="stylesheet" href="{{ $theme['font_url'] }}">
<style>
    :root {
        --sidebar-width: clamp(13.5rem, 16vw, 16.5rem);
        --vx-sidebar-navy: {{ $theme['sidebar'] }};
        --vx-sidebar-mid: {{ $theme['sidebar_mid'] }};
        --vx-sidebar-deep: {{ $theme['sidebar_deep'] }};
        --vx-accent-blue: {{ $theme['accent'] }};
        --vx-accent-light: {{ $theme['accent_light'] }};
        --vx-accent-soft: {{ $theme['accent_soft'] }};
        --premium-navy: {{ $theme['sidebar'] }};
        --premium-navy-soft: {{ $theme['sidebar_mid'] }};
        --premium-navy-muted: {{ $theme['sidebar_deep'] }};
        --premium-accent: {{ $theme['accent'] }};
        --premium-accent-teal: {{ $theme['accent'] }};
        --premium-accent-light: {{ $theme['accent_light'] }};
        --premium-surface: {{ $theme['surface'] }};
        --premium-bg: {{ $theme['bg'] }};
        --premium-text: {{ $theme['text'] }};
        --premium-muted: {{ $theme['muted'] }};
        --premium-border: {{ $theme['border'] }};
        --premium-radius: {{ $theme['radius'] }};
        --c-primary-500: {{ $theme['accent'] }};
        --c-primary-600: {{ $theme['accent'] }};
        --c-primary-700: {{ $theme['sidebar'] }};
        --c-bg-body: {{ $theme['bg'] }};
        --c-bg-card: {{ $theme['surface'] }};
        --c-text-main: {{ $theme['text'] }};
        --c-text-secondary: {{ $theme['muted'] }};
        --c-sidebar: {{ $theme['sidebar'] }};
        --c-line: {{ $theme['border'] }};
        --vx-nav-active-bg: {{ $theme['nav_active_bg'] }};
        --vx-nav-active-border: {{ $theme['nav_active_border'] }};
        --vx-nav-active-shadow: {{ $theme['nav_active_shadow'] }};
    }

    html {
        font-size: {{ $theme['html_size'] }};
    }

    body.theme-modern,
    body.theme-executive,
    body.theme-dense,
    body.theme-glass,
    body {
        font-family: {!! $theme['font_stack'] !!};
        -webkit-font-smoothing: antialiased;
    }

    .workspace-executive .bg-indigo-600,
    .workspace-executive .bg-indigo-500 {
        background-color: var(--vx-accent-blue) !important;
    }
    .workspace-executive .hover\:bg-indigo-700:hover,
    .workspace-executive .hover\:bg-indigo-500:hover {
        background-color: #b45309 !important;
    }
    .workspace-executive .text-indigo-600,
    .workspace-executive .text-indigo-700 {
        color: var(--vx-accent-blue) !important;
    }
    .workspace-executive .border-indigo-200,
    .workspace-executive .hover\:border-indigo-200:hover {
        border-color: var(--vx-accent-soft) !important;
    }
    .workspace-executive .shadow-indigo-600\/25 {
        --tw-shadow-color: rgba(217, 119, 6, 0.25);
    }
</style>
