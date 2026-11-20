{{-- One Work board card. Laptops: drag-and-drop with buttons. Phones: swipe right = done,
     swipe left = tomorrow, tap = quick-action panel (see _script). --}}
@php
    $sheet = [
        'key' => $item['key'],
        'type' => $item['type'],
        'id' => $item['id'] ?? null,
        'kind' => $tag[$item['kind']][0],
        'title' => $item['title'],
        'client' => $item['client_name'],
        'phone' => $item['client_phone'] ?? null,
        'due' => $item['due_text'],
        'over' => $item['urgency'] === 'over',
        'who' => $item['who'],
        'who_id' => $item['who_id'] ?? null,
        'stage' => $item['stage'],
        'complete_url' => $item['complete_url'],
        'complete_method' => $item['complete_method'],
        'complete_payload' => $item['complete_payload'],
        'complete_label' => $item['complete_label'],
        'link_url' => $item['link_url'],
        'start' => (bool) $item['start_payload'],
        'pay' => $item['pay'] ?? null,
        'remind_url' => $item['remind_url'],
        'open_url' => $item['open_url'],
        'note_url' => $item['note_url'] ?? null,
        'time_url' => $item['time_url'] ?? null,
        'can_reassign' => $item['type'] === 'task' && ! empty($reassignStaff),
    ];
@endphp
<article data-dash-item draggable="true"
    data-type="{{ $item['type'] }}"
    data-label="{{ $item['title'] }}"
    data-stage="{{ $item['stage'] }}"
    data-key="{{ $item['key'] }}"
    data-sheet='@json($sheet)'
    @if($item['type'] === 'task') data-status-url="{{ $item['complete_url'] }}" @endif
    @if($item['complete_url']) data-complete-url="{{ $item['complete_url'] }}" data-complete-method="{{ $item['complete_method'] }}" data-complete-payload='@json($item['complete_payload'])' @endif
    @if(!empty($item['pay'])) data-pay='@json($item['pay'])' @endif
    class="relative shrink-0 cursor-grab overflow-hidden rounded-xl border border-slate-200 bg-white active:cursor-grabbing max-md:shadow-sm">
    <div class="md:hidden pointer-events-none absolute inset-0 flex items-center justify-between px-5 text-sm font-bold opacity-0" data-swipe-under>
        <span class="text-white" data-swipe-left-label>✓ {{ $item['complete_url'] || !empty($item['pay']) ? ($item['pay'] ? 'Payment' : 'Done') : 'Open' }}</span>
        <span class="text-white" data-swipe-right-label>Tomorrow →</span>
    </div>
    <div class="relative bg-white p-3 max-md:p-4" data-swipe-face>
        <div class="flex items-start justify-between gap-2">
            <span class="flex items-center gap-1.5">
                <span class="rounded-md px-2 py-0.5 text-xs font-bold {{ $tag[$item['kind']][1] }}">{{ $tag[$item['kind']][0] }}</span>
                @if($item['who'])
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-900" title="{{ $item['who'] }}">{{ \Illuminate\Support\Str::of($item['who'])->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
                @endif
            </span>
            <span class="whitespace-nowrap text-xs {{ $item['urgency'] === 'over' ? 'font-semibold text-red-700' : 'text-slate-600' }}">{{ $item['due_text'] }}</span>
        </div>
        <a href="{{ $item['open_url'] }}" class="mt-2 block text-sm font-semibold text-slate-900 hover:text-indigo-700 max-md:text-[15px]" data-card-title>{{ $item['title'] }}</a>
        <p class="text-[13px] text-slate-600">{{ $item['client_name'] }}</p>
        <div class="mt-2.5 border-t border-slate-100 pt-2.5 max-md:hidden">
            @include('dashboard.layouts._actions', ['item' => $item, 'showStart' => true])
        </div>
    </div>
</article>
