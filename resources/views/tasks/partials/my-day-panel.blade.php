@php
    $tasksToday = $tasksToday ?? collect();
    $tasksUpcoming = $tasksUpcoming ?? collect();
    $compact = $compact ?? false;
@endphp
<div class="glass-card p-6 {{ $compact ? '' : 'h-full' }}" data-demo-tour="my-day">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <div>
            <p class="glass-section-title mb-0">☀️ My Day</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ auth()->user()->name }} · {{ now()->format('l, d M Y') }}</p>
        </div>
        <a href="{{ route('tasks.my-day') }}" class="text-indigo-600 text-xs font-semibold hover:text-indigo-800">Full view →</a>
    </div>
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Due today & overdue ({{ $tasksToday->count() }})</p>
    <div class="space-y-2 mb-4">
        @forelse($tasksToday->take($compact ? 4 : 8) as $task)
        <div class="rounded-xl border border-gray-100 bg-gray-50/80 px-4 py-3" data-my-day-task-card>
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $task->title }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $task->client?->name ?? 'Internal' }} · {{ $task->priority }}</p>
                </div>
                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-white border border-gray-200 text-gray-600 shrink-0" data-my-day-status>{{ $task->status }}</span>
            </div>
            <div class="mt-2 flex flex-wrap gap-2 items-center">
                @if($task->status !== \App\Models\Task::STATUS_IN_PROGRESS)
                <form action="{{ route('tasks.update-status', $task) }}" method="POST" class="inline" data-my-day-status-form data-status-label="{{ \App\Models\Task::STATUS_IN_PROGRESS }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ \App\Models\Task::STATUS_IN_PROGRESS }}">
                    <button type="submit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">Start</button>
                </form>
                @endif
                <form action="{{ route('tasks.update-status', $task) }}" method="POST" class="inline" data-my-day-status-form data-status-label="{{ \App\Models\Task::STATUS_COMPLETED }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ \App\Models\Task::STATUS_COMPLETED }}">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white">Done</button>
                </form>
                @can('delete', $task)
                <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="inline ml-auto" onsubmit="return confirm('Delete this task?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">Delete</button>
                </form>
                @endcan
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-500 rounded-xl border border-dashed border-gray-200 p-4 text-center">Nothing due today — you're caught up.</p>
        @endforelse
    </div>
    @if($tasksUpcoming->isNotEmpty())
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Coming up</p>
    <ul class="space-y-1.5">
        @foreach($tasksUpcoming->take(4) as $task)
        <li class="flex justify-between gap-2 text-sm">
            <span class="truncate text-gray-800">{{ $task->title }}</span>
            <span class="text-xs text-gray-500 shrink-0">{{ $task->due_date?->format('d M') }}</span>
        </li>
        @endforeach
    </ul>
    @endif
</div>
