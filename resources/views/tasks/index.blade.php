@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <div class="flex items-center space-x-4">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tasks</h2>
        <div class="hidden md:flex flex-wrap gap-2">
            <a href="{{ route('tasks.index', array_merge(request()->all(), ['view' => 'list'])) }}"
                class="px-4 py-2 text-sm font-bold rounded-full transition-all duration-200 shadow-sm border 
               {{ $view !== 'board' 
                   ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' 
                   : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
                List View
            </a>
            <a href="{{ route('tasks.index', array_merge(request()->all(), ['view' => 'board'])) }}"
                class="px-4 py-2 text-sm font-bold rounded-full transition-all duration-200 shadow-sm border 
               {{ $view === 'board' 
                   ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' 
                   : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
                Board View
            </a>
        </div>
    </div>
    @can('create', App\Models\Task::class)
    <a href="{{ route('tasks.create') }}" class="bg-primary-600 hover:bg-primary-700 text-white text-sm px-4 py-2 rounded shadow">
        + New Task
    </a>
    @endcan
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-bg-card p-4 rounded shadow">
        <form method="GET" action="{{ route('tasks.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="view" value="{{ $view }}">

            <!-- Status (Only in list view usually, but lets keep for board to filter generic items) -->
            <div>
                <label for="status" class="block text-sm font-medium text-text-main">Status</label>
                <select name="status" id="status" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['Pending', 'In Progress', 'On Hold', 'Completed'] as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority -->
            <div>
                <label for="priority" class="block text-sm font-medium text-text-main">Priority</label>
                <select name="priority" id="priority" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    @foreach(['High', 'Medium', 'Normal', 'Low'] as $priority)
                    <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>{{ $priority }}</option>
                    @endforeach
                </select>
            </div>

            @unless(auth()->user()?->isArticle())
            <!-- Assigned To -->
            <div>
                <label for="assigned_to" class="block text-sm font-medium text-text-main">Assigned To</label>
                <select name="assigned_to" id="assigned_to" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            @endunless

            <div class="flex items-end">
                <a href="{{ route('tasks.index', ['view' => $view]) }}" class="text-sm text-text-secondary hover:text-text-main underline">Clear Filters</a>
            </div>
        </form>
    </div>

    @if($view === 'board')
    <!-- KANBAN BOARD VIEW -->
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
    <!-- LIST VIEW (Original) -->
    <div class="bg-bg-card shadow overflow-hidden sm:rounded-lg">
        @if($tasks->isEmpty())
        @include('partials.empty-state', [
            'title' => 'No tasks found',
            'description' => 'Create a task or adjust filters to see work items here.',
            'icon' => 'tasks',
            'actionLabel' => auth()->user()?->can('create', App\Models\Task::class) ? 'New Task' : null,
            'actionUrl' => auth()->user()?->can('create', App\Models\Task::class) ? route('tasks.create') : null,
        ])
        @else
        <table class="min-w-full">
            <thead class="bg-bg-body">
                <tr class="border-b border-line">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-main uppercase tracking-wider">Title</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-main uppercase tracking-wider">Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-main uppercase tracking-wider">Priority</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-main uppercase tracking-wider">Due Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-main uppercase tracking-wider">Assigned To</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-main uppercase tracking-wider">Status</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-bg-card">
                @foreach($tasks as $task)
                <tr class="border-b border-line">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-text-main">{{ $task->title }}</div>
                        <div class="text-xs text-text-secondary">{{ Str::limit($task->description, 50) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ $task->client ? $task->client->name : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $task->priority === 'High' ? 'bg-red-100 text-red-800' : 
                                      ($task->priority === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                            {{ $task->priority }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ $task->due_date ? $task->due_date->format('d M Y') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        {{ $task->assignee ? $task->assignee->name : 'Unassigned' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        @if(auth()->user()?->isArticle())
                        <select class="rounded-md border-gray-300 text-sm" onchange="updateTaskStatus({{ $task->id }}, this.value)">
                            @foreach(['Pending', 'In Progress', 'On Hold', 'Completed'] as $status)
                            <option value="{{ $status }}" {{ $task->status === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                        @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $task->status === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $task->status }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        @can('update', $task)
                        <a href="{{ route('tasks.edit', $task) }}" class="text-primary-600 hover:text-primary-900">Edit</a>
                        @else
                        <span class="text-gray-400">—</span>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">
            {!! $tasks->links() !!}
        </div>
        @endif
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    function updateTaskStatus(taskId, status) {
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
                }
            })
            .catch(() => {
                alert('Something went wrong');
                location.reload();
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        if ("{{ $view }}" !== 'board') return;

        const columns = document.querySelectorAll('.kanban-column');

        columns.forEach(column => {
            new Sortable(column, {
                group: 'kanban', // set both lists to same group
                animation: 150,
                ghostClass: 'bg-indigo-50',
                onEnd: function(evt) {
                    const itemEl = evt.item;
                    const newStatus = evt.to.getAttribute('data-status');
                    const taskId = itemEl.getAttribute('data-id');

                    // If dropped in the same column, do nothing
                    if (evt.from === evt.to) return;

                    // Update Status via AJAX
                    fetch(`/tasks/${taskId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                status: newStatus
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Status updated');
                            } else {
                                alert('Failed to update status');
                                // Revert (reload page for now to keep simple)
                                location.reload();
                            }
                        })
                        .catch(year => {
                            console.error('Error:', year);
                            alert('Something went wrong');
                            location.reload();
                        });
                }
            });
        });
    });
</script>
@endsection