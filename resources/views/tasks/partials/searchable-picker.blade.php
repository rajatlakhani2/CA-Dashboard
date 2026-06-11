{{-- Searchable dropdown (Alpine). Props: name, label, placeholder, prefix, hint, compact, tableCell, showRole --}}
@php
    $prefix = $prefix ?? 'item';
    $compact = $compact ?? false;
    $tableCell = $tableCell ?? false;
    $required = $required ?? false;
    $showRole = $showRole ?? false;
    $inputClass = ($compact || $tableCell)
        ? 'block w-full rounded-lg border-gray-300 py-2 pl-3 pr-16 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20'
        : 'block w-full rounded-xl border-gray-300 py-2.5 pl-3.5 pr-16 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20';
    $labelClass = $compact ? 'sr-only' : 'block text-sm font-semibold text-gray-800 mb-2';
@endphp
<div class="relative" @click.outside="{{ $prefix }}Open = false">
    @unless($tableCell)
    <label class="{{ $labelClass }}" :for="'{{ $prefix }}-search'">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endunless
    @if(!empty($name))
    <input type="hidden" name="{{ $name }}" :value="{{ $prefix }}Id ?? ''">
    @endif
    <div class="relative">
        <input type="text"
            id="{{ $prefix }}-search"
            x-model="{{ $prefix }}Search"
            @focus="{{ $prefix }}OnFocus()"
            @input="{{ $prefix }}OnInput()"
            @keydown.escape="{{ $prefix }}Open = false"
            @keydown.arrow-down.prevent="{{ $prefix }}HighlightNext()"
            @keydown.arrow-up.prevent="{{ $prefix }}HighlightPrev()"
            @keydown.enter.prevent="{{ $prefix }}SelectHighlighted()"
            @keydown.tab="{{ $prefix }}Open = false"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            role="combobox"
            :aria-expanded="{{ $prefix }}Open"
            aria-autocomplete="list"
            class="{{ $inputClass }}"
            :class="{{ $prefix }}Id ? 'bg-indigo-50/80 border-indigo-300 text-indigo-900 font-medium' : 'bg-white'">
        <div class="absolute right-1 top-1/2 -translate-y-1/2 flex items-center gap-0.5">
            <button type="button" @click="{{ $prefix }}Clear()"
                x-show="{{ $prefix }}Id"
                x-cloak
                class="p-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100"
                aria-label="Clear selection">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <button type="button" @click="{{ $prefix }}ToggleOpen()"
                class="p-1.5 rounded-md text-gray-400 hover:text-indigo-600 hover:bg-indigo-50"
                :aria-label="{{ $prefix }}Open ? 'Close list' : 'Open list'">
                <svg class="h-4 w-4 transition-transform duration-200" :class="{{ $prefix }}Open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
        <div x-show="{{ $prefix }}Open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-cloak
            class="absolute z-40 mt-1.5 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl ring-1 ring-black/5">
            <div class="flex items-center justify-between gap-2 border-b border-gray-100 bg-gray-50/90 px-3 py-2 text-[11px] font-semibold text-gray-500">
                <span x-text="{{ $prefix }}DropdownHint()"></span>
                <span class="text-indigo-600" x-text="{{ $prefix }}Filtered().length + ' shown'"></span>
            </div>
            <div class="max-h-52 overflow-y-auto py-1" role="listbox">
                <template x-for="(opt, idx) in {{ $prefix }}Filtered()" :key="(opt.id ?? 'empty') + '-' + idx">
                    <button type="button" @click="{{ $prefix }}Select(opt)"
                        @mouseenter="{{ $prefix }}HighlightIndex = idx"
                        class="w-full text-left px-3 py-2.5 text-sm flex items-center justify-between gap-2 transition-colors"
                        :class="idx === {{ $prefix }}HighlightIndex ? 'bg-indigo-50 text-indigo-900' : 'text-gray-700 hover:bg-gray-50'"
                        role="option"
                        :aria-selected="String({{ $prefix }}Id) === String(opt.id)">
                        <span class="min-w-0 flex-1">
                            <span class="block font-medium truncate" x-text="opt.name"></span>
                            @if($showRole)
                            <span class="block text-[10px] text-gray-400 mt-0.5" x-show="opt.role" x-text="opt.role"></span>
                            @endif
                        </span>
                        <svg x-show="String({{ $prefix }}Id) === String(opt.id)" class="h-4 w-4 shrink-0 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </template>
                <p x-show="{{ $prefix }}Open && !{{ $prefix }}Filtered().length"
                    class="px-3 py-4 text-sm text-center text-gray-500">
                    No matches — try a different search
                </p>
            </div>
        </div>
    </div>
    @if(!empty($hint) && !$compact && !$tableCell)
    <p class="mt-1.5 text-xs text-gray-500" x-show="!{{ $prefix }}Id">{{ $hint }}</p>
    @endif
</div>
