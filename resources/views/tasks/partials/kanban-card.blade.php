<div id="task-card-{{ $task->id }}" data-id="{{ $task->id }}" class="bg-white p-3 rounded-lg shadow-sm border-l-4 {{ $task->priority === 'High' ? 'border-red-500' : ($task->priority === 'Medium' ? 'border-yellow-500' : 'border-green-500') }} hover:shadow-md cursor-move transition-all active:cursor-grabbing group relative">

    <div class="flex justify-between items-start mb-1">
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide
            {{ $task->priority === 'High' ? 'bg-red-50 text-red-700' : ($task->priority === 'Medium' ? 'bg-yellow-50 text-yellow-700' : 'bg-green-50 text-green-700') }}">
            {{ $task->priority }}
        </span>
        <a href="{{ route('tasks.edit', $task) }}" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-indigo-600 transition-opacity">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
            </svg>
        </a>
    </div>

    <h4 class="text-sm font-bold text-gray-800 leading-snug mb-1">{{ $task->title }}</h4>

    <div class="text-xs text-gray-500 mb-3 truncate flex items-center">
        <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
        </svg>
        {{ $task->client->name ?? 'No Client' }}
    </div>

    <div class="flex justify-between items-center pt-2 border-t border-gray-100">
        <div class="flex items-center -space-x-2 overflow-hidden">
            @if($task->assignee)
            <div class="h-6 w-6 rounded-full bg-indigo-100 ring-2 ring-white flex items-center justify-center text-[10px] text-indigo-700 font-bold" title="{{ $task->assignee->name }}">
                {{ substr($task->assignee->name, 0, 1) }}
            </div>
            @else
            <div class="h-6 w-6 rounded-full bg-gray-100 ring-2 ring-white flex items-center justify-center text-[10px] text-gray-500" title="Unassigned">
                ?
            </div>
            @endif
        </div>

        @if($task->due_date)
        <div class="flex items-center text-[11px] font-medium {{ $task->due_date->isPast() && $task->status !== 'Completed' ? 'text-red-600 bg-red-50 px-2 py-0.5 rounded' : 'text-gray-500' }}">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            {{ $task->due_date->format('M d') }}
        </div>
        @endif
    </div>
</div>