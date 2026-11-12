{{-- "Customise" panel: choose which Action inbox sections to show and their order. --}}
@php
    $hidden = array_values(array_diff(array_keys($sectionOptions), $sections));
    $initial = collect($sections)->map(fn ($k) => ['key' => $k, 'label' => $sectionOptions[$k], 'on' => true])
        ->merge(collect($hidden)->map(fn ($k) => ['key' => $k, 'label' => $sectionOptions[$k], 'on' => false]))
        ->values();
@endphp
<div x-data="{ open: false, list: @js($initial),
        move(i, d) { const j = i + d; if (j < 0 || j >= this.list.length) return; const t = this.list[i]; this.list[i] = this.list[j]; this.list[j] = t; } }"
     class="relative -mb-2 flex justify-end">
    <button type="button" @click="open = !open" :aria-expanded="open"
        class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:border-slate-400">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h6"/></svg>
        Customise sections
    </button>

    <form x-show="open" x-cloak @keydown.escape.window="open = false" method="POST" action="{{ route('dashboard.inbox.sections') }}"
          class="absolute right-0 top-12 z-30 w-[22rem] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
        @csrf
        <p class="text-base font-bold text-slate-900">Your dashboard sections</p>
        <p class="mb-3 mt-0.5 text-sm text-slate-600">Tick what you want to see and use the arrows to change the order. Only you see this.</p>
        <ul class="flex flex-col gap-1">
            <template x-for="(s, i) in list" :key="s.key">
                <li class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                    <label class="flex flex-1 items-center gap-2 text-sm text-slate-800">
                        <input type="checkbox" x-model="s.on" class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                        <span x-text="s.label"></span>
                    </label>
                    <button type="button" @click="move(i, -1)" :disabled="i === 0" class="h-8 w-8 rounded-md text-slate-600 hover:bg-slate-200 disabled:opacity-30" :aria-label="'Move ' + s.label + ' up'">↑</button>
                    <button type="button" @click="move(i, 1)" :disabled="i === list.length - 1" class="h-8 w-8 rounded-md text-slate-600 hover:bg-slate-200 disabled:opacity-30" :aria-label="'Move ' + s.label + ' down'">↓</button>
                    <template x-if="s.on"><input type="hidden" name="sections[]" :value="s.key"></template>
                </li>
            </template>
        </ul>
        <div class="mt-4 flex items-center justify-between gap-2">
            <button type="submit" name="reset" value="1" class="text-sm font-medium text-slate-600 underline">Reset to default</button>
            <div class="flex gap-2">
                <button type="button" @click="open = false" class="min-h-10 rounded-lg border border-slate-300 px-4 text-sm text-slate-700">Cancel</button>
                <button type="submit" class="min-h-10 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Save</button>
            </div>
        </div>
    </form>
</div>
