@if(!empty($dashboardLayouts))
<form method="POST" action="{{ route('dashboard.layout') }}" class="flex items-center gap-2 shrink-0" data-dashboard-layout-switcher>
    @csrf
    <label for="dashboard-layout-switch" class="text-[11px] font-semibold uppercase tracking-wider text-[var(--premium-muted)]">Layout</label>
    <select id="dashboard-layout-switch" name="dashboard_layout" onchange="this.form.submit()"
        class="rounded-lg border border-slate-300 bg-white text-sm text-slate-800 py-1.5 pl-3 pr-8">
        <option value="" {{ empty($dashboardLayoutChoice) ? 'selected' : '' }}>Firm default</option>
        @foreach($dashboardLayouts as $key => $layout)
        <option value="{{ $key }}" {{ ($dashboardLayoutChoice ?? null) === $key ? 'selected' : '' }} {{ $layout['available'] ? '' : 'disabled' }}>{{ $layout['label'] }}{{ $layout['available'] ? '' : ' (coming soon)' }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white">Apply</button></noscript>
</form>
@endif
