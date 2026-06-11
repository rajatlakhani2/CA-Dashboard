@php
    use App\Support\TaskListDisplay;
@endphp
@push('head_styles')
<style>
    .tasks-demo-table { border-collapse: separate; border-spacing: 0; }
    .tasks-demo-table thead th {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.65rem 0.75rem;
        text-align: left;
        white-space: nowrap;
    }
    .tasks-demo-table tbody tr {
        border-bottom: 1px solid #e2e8f0;
        transition: background 0.15s ease;
    }
    .tasks-demo-table tbody tr:hover { background: #f8fafc; }
    .tasks-demo-table tbody td {
        padding: 0.85rem 0.75rem;
        vertical-align: middle;
    }
    .tasks-demo-table .priority-cell { width: 4.5rem; }
    .tasks-demo-table .due-cell { width: 5.5rem; text-align: right; }
    .tasks-priority-bar {
        width: 3px;
        border-radius: 999px;
        align-self: stretch;
        min-height: 2.75rem;
    }
</style>
@endpush

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden" data-demo-tour="tasks-list">
    <div class="overflow-x-auto">
        <table class="tasks-demo-table min-w-full w-full">
            <thead>
                <tr>
                    <th class="priority-cell pl-4">Priority</th>
                    <th>Task</th>
                    <th>Client</th>
                    <th>Assignee</th>
                    <th class="due-cell pr-4">Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                @php
                    $tone = TaskListDisplay::priorityTone($task->priority);
                    $dueContext = TaskListDisplay::dueContextLabel($task);
                    $progress = TaskListDisplay::progressPercent($task);
                    $service = TaskListDisplay::clientServiceLabel($task);
                    $assignee = TaskListDisplay::assigneeLabel($task);
                    $dueUrgent = $task->due_date && $task->due_date->isPast() || $dueContext === 'Due Today';
                @endphp
                <tr class="group cursor-pointer" onclick="window.location='{{ route('tasks.edit', $task) }}'">
                    <td class="priority-cell pl-4">
                        <div class="flex items-stretch gap-2 min-h-[2.75rem]">
                            <span class="tasks-priority-bar {{ $tone['bar'] }}"></span>
                            <span class="inline-flex items-center rounded-md border px-2 py-1 text-[11px] font-bold {{ $tone['badge'] }}">
                                {{ $task->priority }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <p class="text-sm font-bold text-gray-900 group-hover:text-indigo-700 transition-colors">{{ $task->title }}</p>
                        <p class="text-xs mt-0.5 {{ $dueUrgent ? 'text-rose-600 font-semibold' : 'text-gray-500' }}">
                            {{ $dueContext ?: ($task->status) }}
                        </p>
                    </td>
                    <td>
                        <p class="text-sm font-semibold text-gray-800">{{ $task->client?->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $service }}</p>
                    </td>
                    <td>
                        <p class="text-sm font-semibold text-gray-800">{{ $assignee }}</p>
                        <div class="mt-1 flex items-center gap-2 max-w-[8rem]">
                            <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $progress }}%"></div>
                            </div>
                            <span class="text-[10px] font-bold text-indigo-600 tabular-nums">{{ $progress }}%</span>
                        </div>
                    </td>
                    <td class="due-cell pr-4">
                        <span class="text-sm font-bold {{ $dueUrgent ? 'text-rose-600' : 'text-gray-700' }}">
                            {{ $task->due_date ? $task->due_date->format('d M') : '—' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(method_exists($tasks, 'links'))
    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/50">
        {!! $tasks->withQueryString()->links() !!}
    </div>
    @endif
</div>
