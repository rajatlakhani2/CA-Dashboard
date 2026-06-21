@props([
    'id',
    'title',
    'subtitle' => null,
    'defaultCollapsed' => false,
])

<div
    {{ $attributes->merge(['class' => 'dashboard-widget executive-summary__widget exec-widget']) }}
    data-dashboard-widget="{{ $id }}"
    @if($defaultCollapsed) data-default-collapsed="1" @endif
>
    <div class="exec-widget__chrome">
        <button type="button" class="dashboard-drag-handle exec-widget__drag" title="Drag to reorder (Alt+↑↓ when focused)" aria-label="Drag to reorder" tabindex="0">
            <span aria-hidden="true">⋮⋮</span>
            <span class="exec-widget__drag-label">Drag</span>
        </button>
        <div class="exec-widget__meta min-w-0 flex-1">
            <p class="exec-widget__title">{{ $title }}</p>
            @if($subtitle)
            <p class="exec-widget__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        <button type="button" class="exec-widget__collapse" aria-expanded="{{ $defaultCollapsed ? 'false' : 'true' }}" title="Collapse or expand" aria-label="Collapse or expand section">
            <span class="exec-widget__collapse-icon">{{ $defaultCollapsed ? '▶' : '▼' }}</span>
        </button>
    </div>
    <div class="exec-widget__body" @if($defaultCollapsed) hidden @endif>
        <div class="exec-widget__content">
            {{ $slot }}
        </div>
    </div>
    <div class="exec-widget__resize-layer" @if($defaultCollapsed) hidden @endif aria-hidden="false">
        <div class="exec-widget__resize exec-widget__resize--bottom" role="separator" aria-label="Resize height" title="Drag bottom edge to change height"></div>
        <div class="exec-widget__resize exec-widget__resize--right" role="separator" aria-label="Resize width" title="Drag right edge to change width"></div>
        <div class="exec-widget__resize exec-widget__resize--corner" role="separator" aria-label="Resize width and height" title="Drag corner to resize · double-click to reset">
            <span class="exec-widget__resize-grip" aria-hidden="true">⤡</span>
        </div>
    </div>
</div>
