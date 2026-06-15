@php
    $tasks = $dueTomorrowTasks ?? collect();
    $dues = $dueTomorrowDues ?? collect();
    $total = $tasks->count() + $dues->count();
    $hideHeader = $hideHeader ?? false;
@endphp
<div class="{{ $hideHeader ? 'exec-widget__inner exec-tomorrow-panel' : 'exec-summary__card exec-summary__card--compact exec-tomorrow-panel' }}">
    @if($hideHeader)
    <div class="flex justify-end mb-2">
        <a href="{{ route('tasks.index', ['due' => 'next_7']) }}" class="text-[10px] font-semibold text-indigo-600">All →</a>
    </div>
    @else
    <div class="flex items-center justify-between gap-2 mb-2">
        <p class="exec-summary__label mb-0">Due tomorrow ({{ $total }})</p>
        <a href="{{ route('tasks.index', ['due' => 'next_7']) }}" class="text-[10px] font-semibold text-indigo-600">All →</a>
    </div>
    @endif
    <ul class="space-y-1.5 max-h-[200px] overflow-y-auto custom-scrollbar pr-0.5">
        @foreach($tasks as $task)
        <li>
            <a href="{{ route('tasks.index') }}" class="exec-summary__row py-1.5 border-indigo-100 bg-indigo-50/40">
                <span class="text-[10px] font-bold uppercase text-indigo-600 shrink-0">Task</span>
                <span class="text-xs font-semibold truncate">{{ $task->title }}</span>
                <span class="text-[10px] text-gray-500 truncate shrink-0">{{ $task->client?->name ?? 'Internal' }}</span>
            </a>
        </li>
        @endforeach
        @foreach($dues as $due)
        <li>
            <a href="{{ $due['url'] }}" class="exec-summary__row py-1.5 border-violet-100 bg-violet-50/40">
                <span class="text-[10px] font-bold uppercase text-violet-700 shrink-0">Due</span>
                <span class="text-xs font-semibold truncate">{{ $due['service_name'] }}</span>
                <span class="text-[10px] text-gray-500 truncate shrink-0">{{ $due['client_name'] }}</span>
            </a>
        </li>
        @endforeach
        @if($total === 0)
        <li class="text-xs text-gray-500 text-center py-4 border border-dashed border-slate-200 rounded-lg">Nothing due tomorrow.</li>
        @endif
    </ul>
</div>
