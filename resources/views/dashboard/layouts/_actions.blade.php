{{-- Action buttons for one inbox / board item. Expects $item. --}}
<div class="flex flex-wrap items-center gap-1.5">
    @if($item['complete_url'])
    <button type="button"
        class="min-h-9 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white hover:bg-slate-700"
        data-dash-action="complete"
        data-url="{{ $item['complete_url'] }}"
        data-method="{{ $item['complete_method'] }}"
        data-payload='@json($item['complete_payload'])'
        data-label="{{ $item['title'] }}">{{ $item['complete_label'] }}</button>
    @elseif($item['link_url'])
    <a href="{{ $item['link_url'] }}" class="inline-flex min-h-9 items-center rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white hover:bg-slate-700">{{ $item['complete_label'] }}</a>
    @endif

    @if(($showStart ?? false) && $item['start_payload'])
    <button type="button"
        class="min-h-9 rounded-lg border border-slate-300 bg-white px-3 text-xs font-medium text-slate-700 hover:border-slate-400"
        data-dash-action="start"
        data-url="{{ $item['complete_url'] }}"
        data-method="PATCH"
        data-payload='@json($item['start_payload'])'
        data-label="{{ $item['title'] }}">Start</button>
    @endif

    @if($item['remind_url'])
    <form method="POST" action="{{ $item['remind_url'] }}" class="contents">
        @csrf
        <button type="submit" class="min-h-9 rounded-lg border border-slate-300 bg-white px-3 text-xs font-medium text-slate-700 hover:border-slate-400">WhatsApp</button>
    </form>
    @endif

    <button type="button"
        class="min-h-9 rounded-lg border border-slate-300 bg-white px-3 text-xs font-medium text-slate-700 hover:border-slate-400"
        data-dash-action="snooze"
        data-key="{{ $item['key'] }}"
        data-label="{{ $item['title'] }}">Snooze</button>
</div>
