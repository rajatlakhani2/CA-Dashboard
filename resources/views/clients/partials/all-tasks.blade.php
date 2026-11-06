{{-- Client 360 → All Tasks. Expects $tasks, $client. --}}
@php
    $tone = fn ($p) => \App\Support\TaskListDisplay::priorityTone($p === 'Normal' ? 'Medium' : (string) $p);
    $open = $tasks->reject(fn ($t) => in_array($t->status, \App\Models\Task::TERMINAL_STATUSES, true));
    $done = $tasks->filter(fn ($t) => in_array($t->status, \App\Models\Task::TERMINAL_STATUSES, true));
@endphp
<div class="grid grid-cols-3 gap-3">
    @foreach(\App\Models\Task::PRIORITIES as $p)
    @php $n = $open->filter(fn ($t) => ($t->priority === 'Normal' ? 'Medium' : $t->priority) === $p)->count(); @endphp
    <a href="{{ route('tasks.priority', ['client_id' => $client->id, 'p' => $p]) }}" class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm hover:border-indigo-300">
        <div class="flex items-center gap-2 text-xs font-bold text-gray-600"><span class="h-2 w-2 rounded-full {{ $tone($p)['bar'] }}"></span>{{ $p }}</div>
        <div class="text-2xl font-extrabold text-gray-900 tabular-nums">{{ $n }}</div>
    </a>
    @endforeach
</div>

@foreach(['Open' => $open, 'Completed' => $done] as $label => $rows)
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-900">{{ $label }} tasks</h3>
        <span class="text-xs text-gray-500">{{ $rows->count() }}</span>
    </div>
    @if($rows->isEmpty())
    <p class="px-4 py-6 text-sm text-gray-500 text-center">No {{ strtolower($label) }} tasks.</p>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-2 text-left">Priority</th>
                    <th class="px-4 py-2 text-left">Task</th>
                    <th class="px-4 py-2 text-left hidden sm:table-cell">Assignee</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-right">Deadline</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($rows as $task)
                @php $late = $label === 'Open' && $task->due_date && $task->due_date->lt(now()->startOfDay()); @endphp
                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('tasks.edit', $task) }}'">
                    <td class="px-4 py-2.5">
                        <span class="inline-flex rounded-md border px-2 py-0.5 text-[11px] font-bold {{ $tone($task->priority)['badge'] }}">{{ $task->priority === 'Normal' ? 'Medium' : $task->priority }}</span>
                    </td>
                    <td class="px-4 py-2.5 font-semibold text-gray-900">{{ $task->title }}</td>
                    <td class="px-4 py-2.5 text-gray-600 hidden sm:table-cell">{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                    <td class="px-4 py-2.5 text-gray-600">{{ $task->status }}</td>
                    <td class="px-4 py-2.5 text-right whitespace-nowrap font-semibold {{ $late ? 'text-rose-600' : 'text-gray-700' }}">
                        {{ $task->due_date?->format('d M Y') ?? '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endforeach
