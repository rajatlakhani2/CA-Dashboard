{{--
    Searchable dropdown (Alpine). Requires parent x-data with matching *Picker state.
    Props: name, label, placeholder, emptyLabel, optionsVar (e.g. clientOptions), state prefix (client / assignee)
--}}
@php
    $prefix = $prefix ?? 'item';
    $optionsVar = $optionsVar ?? 'options';
    $required = $required ?? false;
@endphp
<div class="relative" @click.outside="{{ $prefix }}Open = false">
    <label class="block text-sm font-semibold text-gray-800 mb-2">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
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
            class="block w-full rounded-xl border-gray-300 py-3 pl-4 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :class="{{ $prefix }}Id ? 'bg-indigo-50/50 border-indigo-200' : 'bg-white'">
        <button type="button" @click="{{ $prefix }}Clear()"
            x-show="{{ $prefix }}Id"
            class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Clear selection">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div x-show="{{ $prefix }}Open && {{ $prefix }}Filtered().length" x-cloak
            class="absolute z-30 mt-1 w-full max-h-56 overflow-auto rounded-xl border border-gray-200 bg-white shadow-xl py-1">
            <template x-for="(opt, idx) in {{ $prefix }}Filtered()" :key="opt.id ?? 'empty'">
                <button type="button"
                    @click="{{ $prefix }}Select(opt)"
                    class="w-full text-left px-4 py-2.5 text-sm transition-colors"
                    :class="idx === {{ $prefix }}HighlightIndex ? 'bg-indigo-50 text-indigo-900 font-medium' : 'text-gray-700 hover:bg-gray-50'"
                    x-text="opt.name"></button>
            </template>
        </div>
        <p x-show="{{ $prefix }}Open && !{{ $prefix }}Filtered().length" x-cloak class="absolute z-30 mt-1 w-full rounded-xl border border-gray-200 bg-white shadow-lg px-4 py-3 text-sm text-gray-500">
            No matches. Try another name.
        </p>
    </div>
    <p class="mt-1.5 text-xs text-gray-500" x-show="!{{ $prefix }}Id">{{ $hint ?? 'Type to search, or pick from the list.' }}</p>
    <p class="mt-1.5 text-xs font-medium text-indigo-700" x-show="{{ $prefix }}Id" x-text="'Selected: ' + ({{ $prefix }}Label || '')"></p>
</div>
