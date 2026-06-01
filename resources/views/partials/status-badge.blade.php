@props(['status', 'type' => null])

@php
    $normalized = strtolower(trim((string) $status));
    $type = $type ?? match (true) {
        in_array($normalized, ['paid', 'completed', 'active', 'approved'], true) => 'success',
        in_array($normalized, ['overdue', 'closed', 'cancelled'], true) => 'danger',
        in_array($normalized, ['pending', 'partially paid', 'on-hold', 'draft', 'in progress'], true) => 'warning',
        default => 'neutral',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge-status badge-status-{$type}"]) }}>{{ $status }}</span>
