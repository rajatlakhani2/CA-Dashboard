{{-- Searchable dropdown (Alpine). Props: name, label, placeholder, prefix, hint, compact, tableCell --}}
@php
    $prefix = $prefix ?? 'item';
    $compact = $compact ?? false;
    $tableCell = $tableCell ?? false;
    $required = $required ?? false;
    $inputClass = ($compact || $tableCell)
        ? 'block w-full rounded-lg border-gray-300 py-2 pl-3 pr-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500'
        : 'block w-full rounded-xl border-gray-300 py-3 pl-4 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $labelClass = $compact ? 'block text-xs font-semibold text-gray-700 mb-1' : 'block text-sm font-semibold text-gray-800 mb-2';
@endphp
<div class="relative" @click.outside="{{ $prefix }}Open = false">
    @unless($tableCell)
    <label class="{{ $labelClass }}">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endunless
    @if(!empty($name))
    <input type="hidden" name="{{ $name }}" :value="{{ $prefix }}Id ?? ''">
    @endif
    <div class="relative">
        <input type="text"
            x-model="{{ $prefix }}Search"
            @focus="{{ $prefix }}Open = true"
            @input="{{ $prefix }}Open = true"
            @keydown.escape="{{ $prefix }}Open = false"
            @keydown.arrow-down.prevent="{{ $prefix }}HighlightNext()"
            @keydown.arrow-up.prevent="{{ $prefix }}HighlightPrev()"
            @keydown.enter.prevent="{{ $prefix }}SelectHighlighted()"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="{{ $inputClass }}"
            :class="{{ $prefix }}Id ? 'bg-indigo-50/60 border-indigo-300' : 'bg-white'">
        <button type="button" @click="{{ $prefix }}Clear()"
            x-show="{{ $prefix }}Id"
            class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1 rounded text-gray-400 hover:text-gray-700"
            aria-label="Clear">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div x-show="{{ $prefix }}Open && {{ $prefix }}Filtered().length" x-cloak
            class="absolute z-30 mt-1 w-full max-h-48 overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg py-1">
            <template x-for="(opt, idx) in {{ $prefix }}Filtered()" :key="opt.id ?? 'empty'">
                <button type="button" @click="{{ $prefix }}Select(opt)"
                    class="w-full text-left px-3 py-2 text-sm"
                    :class="idx === {{ $prefix }}HighlightIndex ? 'bg-indigo-50 text-indigo-900 font-medium' : 'text-gray-700 hover:bg-gray-50'"
                    x-text="opt.name"></button>
            </template>
        </div>
    </div>
    @if(!empty($hint) && ($tableCell || !$compact))
    <p class="mt-1 text-xs text-gray-500" x-show="!{{ $prefix }}Id">{{ $hint }}</p>
    @endif
</div>
