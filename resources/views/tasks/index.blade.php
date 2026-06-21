@extends('layouts.app')

@section('header', 'Tasks')

@section('content')
<div class="space-y-4 max-w-6xl mx-auto" x-data="{ filtersOpen: false }">
    {{-- Toolbar: title row + search / filter / sort --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl font-bold text-gray-900">Tasks</h2>
            @if(request('due') === 'overdue')
            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800">Overdue only</span>
            @elseif(request('due') === 'due_today')
            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-900">Due today</span>
            @elseif(request('due') === 'next_7')
            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-900">Next 7 days</span>
            @elseif(request('due') === 'next_15')
            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-900">Next 15 days</span>
            @endif
        </div>
        @can('create', App\Models\Task::class)
        <a href="{{ route('tasks.create') }}"
            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 transition-colors shrink-0">
            <span class="text-base leading-none">+</span> Create Task
        </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('tasks.index') }}" class="flex flex-col sm:flex-row gap-2 sm:items-center">
        <input type="hidden" name="view" value="{{ $view }}">
        @if(request('due'))
        <input type="hidden" name="due" value="{{ request('due') }}">
        @endif
        <div class="relative flex-1 min-w-0">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" name="q" value="{{ request('q') }}"
                placeholder="Search tasks, clients, assignees…"
                class="block w-full rounded-xl border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
        </div>
        <div class="flex gap-2 shrink-0">
            <button type="button" @click="filtersOpen = !filtersOpen"
                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:border-indigo-300 hover:text-indigo-700 shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
            <select name="sort" onchange="this.form.submit()"
                class="rounded-xl border-gray-200 bg-white py-2.5 pl-3 pr-8 text-sm font-semibold text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="due" @selected(request('sort', 'due') === 'due')>Sort: Due date</option>
                <option value="priority" @selected(request('sort') === 'priority')>Sort: Priority</option>
                <option value="title" @selected(request('sort') === 'title')>Sort: Title</option>
            </select>
        </div>
    </form>

    <div x-show="filtersOpen" x-transition class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('tasks.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="hidden" name="view" value="{{ $view }}">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <input type="hidden" name="sort" value="{{ request('sort', 'due') }}">
            <input type="hidden" name="due" value="{{ request('due') }}">
            <div>
                <label for="status" class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" id="status" class="block w-full rounded-lg border-gray-200 text-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['Pending', 'In Progress', 'On Hold', 'Completed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="priority" class="block text-xs font-semibold text-gray-600 mb-1">Priority</label>
                <select name="priority" id="priority" class="block w-full rounded-lg border-gray-200 text-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['High', 'Medium', 'Normal', 'Low'] as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') == $priority)>{{ $priority }}</option>
                    @endforeach
                </select>
            </div>
            @unless(auth()->user()?->isArticle())
            <div>
                <label for="assigned_to" class="block text-xs font-semibold text-gray-600 mb-1">Assignee</label>
                <select name="assigned_to" id="assigned_to" class="block w-full rounded-lg border-gray-200 text-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(request('assigned_to') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            @endunless
        </form>
        <div class="mt-3 flex gap-3 text-xs">
            <a href="{{ route('tasks.index', ['view' => $view]) }}" class="text-gray-500 hover:text-gray-800 underline">Clear filters</a>
            <a href="{{ route('tasks.index', array_merge(request()->all(), ['view' => 'board'])) }}" class="text-indigo-600 font-semibold hover:underline">Board view</a>
        </div>
    </div>

    @if($view === 'board')
    <div class="flex gap-6 overflow-x-auto pb-6">
        @foreach(['Pending' => 'bg-gray-50', 'In Progress' => 'bg-blue-50', 'On Hold' => 'bg-yellow-50', 'Completed' => 'bg-green-50'] as $columnStatus => $bgClass)
        <div class="w-80 flex-shrink-0 flex flex-col {{ $bgClass }} rounded-lg p-3">
            <h3 class="text-sm font-bold text-gray-700 uppercase mb-3 flex justify-between">
                {{ $columnStatus }}
                <span class="bg-white px-2 py-0.5 rounded-full text-xs shadow-sm">
                    {{ $tasks->where('status', $columnStatus)->count() }}
                </span>
            </h3>
            <div class="flex-1 space-y-3 kanban-column min-h-[200px]" data-status="{{ $columnStatus }}">
                @foreach($tasks->where('status', $columnStatus) as $task)
                @include('tasks.partials.kanban-card', ['task' => $task])
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @else
        @if($tasks->isEmpty())
        @include('partials.empty-state', [
            'title' => 'No tasks found',
            'description' => 'Create a task or adjust filters to see work items here.',
            'icon' => 'tasks',
            'actionLabel' => auth()->user()?->can('create', App\Models\Task::class) ? 'Create Task' : null,
            'actionUrl' => auth()->user()?->can('create', App\Models\Task::class) ? route('tasks.create') : null,
        ])
        @else
        @include('tasks.partials.list-table', ['tasks' => $tasks])
        @endif
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    function updateTaskStatus(taskId, status, selectEl) {
        fetch(`/tasks/${taskId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status }),
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Failed to update status');
                    location.reload();
                    return;
                }
                if (status === 'Completed' && confirm('Task completed. Open Invoices → Unbilled now?')) {
                    window.location.href = '{{ route('invoices.index', ['tab' => 'unbilled']) }}';
                }
            })
            .catch(() => {
                alert('Something went wrong');
                location.reload();
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        if ("{{ $view }}" !== 'board') return;
        document.querySelectorAll('.kanban-column').forEach(column => {
            new Sortable(column, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'bg-indigo-50',
                onEnd: function(evt) {
                    if (evt.from === evt.to) return;
                    const newStatus = evt.to.getAttribute('data-status');
                    const taskId = evt.item.getAttribute('data-id');
                    fetch(`/tasks/${taskId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                alert('Failed to update status');
                                location.reload();
                            }
                        })
                        .catch(() => {
                            alert('Something went wrong');
                            location.reload();
                        });
                }
            });
        });
    });
</script>
@endsection
