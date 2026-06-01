@php
    $isOverdue = $task->due_date && $task->due_date->isPast();
@endphp
<div class="bg-white rounded-lg border border-slate-200 p-2.5 shadow-sm text-xs {{ $isOverdue ? 'border-l-4 border-l-red-500' : '' }}"
    @if($canReassign) draggable="true" @dragstart="dragStart($event, {{ $task->id }})" @endif>
    <a href="{{ route('tasks.edit', $task) }}" class="font-semibold text-slate-900 hover:text-indigo-600 line-clamp-2">{{ $task->title }}</a>
    <p class="text-slate-500 mt-1 truncate">{{ $task->client->name ?? 'Internal' }}</p>
    <div class="flex justify-between items-center mt-2">
        <span class="{{ $isOverdue ? 'text-red-600 font-bold' : 'text-slate-500' }}">
            {{ $task->due_date ? $task->due_date->format('d M') : 'No date' }}
        </span>
        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-medium">{{ $task->priority ?? 'Normal' }}</span>
    </div>
</div>
