@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <div class="flex items-center space-x-4">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            Task Kanban
        </h2>
        <a href="{{ route('tasks.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1 rounded-full">Switch to List View</a>
    </div>
    <a href="{{ route('tasks.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded-md">
        + New Task
    </a>
</div>
@endsection

@section('content')
<div class="h-full overflow-x-auto">
    <div class="flex h-full space-x-6 min-w-max pb-4">

        <!-- Pending Column -->
        <div class="w-80 flex flex-col bg-gray-100 rounded-xl max-h-full">
            <div class="p-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">Pending</h3>
                <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $tasks['Pending']->count() }}</span>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-3" id="kanban-pending" ondrop="drop(event, 'Pending')" ondragover="allowDrop(event)">
                @foreach($tasks['Pending'] as $task)
                @include('tasks.partials.kanban-card', ['task' => $task, 'color' => 'border-l-4 border-gray-400'])
                @endforeach
            </div>
        </div>

        <!-- In Progress Column -->
        <div class="w-80 flex flex-col bg-blue-50 rounded-xl max-h-full">
            <div class="p-4 flex justify-between items-center">
                <h3 class="font-bold text-blue-800">In Progress</h3>
                <span class="bg-blue-200 text-blue-800 text-xs px-2 py-1 rounded-full">{{ $tasks['In Progress']->count() }}</span>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-3" id="kanban-progress" ondrop="drop(event, 'In Progress')" ondragover="allowDrop(event)">
                @foreach($tasks['In Progress'] as $task)
                @include('tasks.partials.kanban-card', ['task' => $task, 'color' => 'border-l-4 border-blue-500'])
                @endforeach
            </div>
        </div>

        <!-- Completed Column -->
        <div class="w-80 flex flex-col bg-green-50 rounded-xl max-h-full">
            <div class="p-4 flex justify-between items-center">
                <h3 class="font-bold text-green-800">Completed</h3>
                <span class="bg-green-200 text-green-800 text-xs px-2 py-1 rounded-full">{{ $tasks['Completed']->count() }}</span>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-3" id="kanban-completed" ondrop="drop(event, 'Completed')" ondragover="allowDrop(event)">
                @foreach($tasks['Completed'] as $task)
                @include('tasks.partials.kanban-card', ['task' => $task, 'color' => 'border-l-4 border-green-500'])
                @endforeach
            </div>
        </div>

    </div>
</div>

<script>
    function allowDrop(ev) {
        ev.preventDefault();
    }

    function drag(ev, id) {
        ev.dataTransfer.setData("text", id);
        ev.target.classList.add('opacity-50');
    }

    function endDrag(ev) {
        ev.target.classList.remove('opacity-50');
    }

    function drop(ev, newStatus) {
        ev.preventDefault();
        var data = ev.dataTransfer.getData("text");
        var el = document.getElementById('task-card-' + data);

        // Find the drop zone (column)
        var dropZone = ev.target.closest('.overflow-y-auto');
        if (dropZone && el) {
            dropZone.appendChild(el);

            // AJAX call to update status
            fetch(`/tasks/${data}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        console.log('Status updated to ' + newStatus);
                        // Optional: Update counters or visual cues
                    } else {
                        alert('Failed to update status');
                    }
                });
        }
    }
</script>
@endsection