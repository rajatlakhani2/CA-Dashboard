@extends('layouts.app')

@section('header', 'Workload Planner')

@section('content')
<div class="space-y-6" x-data="workloadBoard()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <p class="text-sm text-slate-600">
            Open tasks by assignee. <strong>Planned load</strong> uses {{ \App\Services\WorkloadPlannerBuilder::HOURS_PER_OPEN_TASK }}h per open task;
            <strong>logged</strong> is time entries (last 30 days). Drag tasks between columns to reassign.
        </p>
        @if($branches->isNotEmpty())
        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs font-medium text-slate-600">Branch</label>
            <select name="branch_id" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
                <option value="">All branches</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
            <p class="text-xs text-slate-500 uppercase font-bold">Open tasks</p>
            <p class="text-2xl font-black text-slate-900">{{ $totals['open'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
            <p class="text-xs text-slate-500 uppercase font-bold">Overdue</p>
            <p class="text-2xl font-black text-red-600">{{ $totals['overdue'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
            <p class="text-xs text-slate-500 uppercase font-bold">Unassigned</p>
            <p class="text-2xl font-black text-amber-600">{{ $totals['unassigned'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
            <p class="text-xs text-slate-500 uppercase font-bold">Team members</p>
            <p class="text-2xl font-black text-indigo-600">{{ $members->count() }}</p>
        </div>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4 snap-x">
        @if($unassigned->isNotEmpty())
        <div class="flex-shrink-0 w-72 snap-start" data-assignee-id="0">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-2">
                <h3 class="font-bold text-amber-900 text-sm">Unassigned</h3>
                <p class="text-xs text-amber-700">{{ $unassigned->count() }} task(s)</p>
            </div>
            <div class="space-y-2 min-h-[120px] rounded-lg bg-slate-50/80 p-2 border border-dashed border-slate-200"
                @if($canReassign) @dragover.prevent="$el.classList.add('ring-2','ring-indigo-300')" @dragleave="$el.classList.remove('ring-2','ring-indigo-300')" @drop.prevent="dropOn(0, $event)" @endif>
                @foreach($unassigned as $task)
                @include('workload.partials.task-card', ['task' => $task, 'canReassign' => $canReassign])
                @endforeach
            </div>
        </div>
        @endif

        @if($members->isEmpty() && $unassigned->isEmpty())
        <div class="flex-shrink-0 w-full max-w-lg snap-start">
            <div class="bg-white border border-dashed border-slate-200 rounded-xl p-8 text-center">
                <p class="text-sm font-semibold text-slate-800">No team workload yet</p>
                <p class="text-xs text-slate-500 mt-2">Add staff in Settings or the Staff Directory. Their columns appear here once team members are on board.</p>
            </div>
        </div>
        @endif

        @foreach($members as $member)
        <div class="flex-shrink-0 w-72 snap-start" data-assignee-id="{{ $member->user->id }}">
            <div class="bg-white border border-slate-200 rounded-xl p-3 mb-2 shadow-sm">
                <div class="flex justify-between items-start gap-2">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">{{ $member->user->name }}</h3>
                        <p class="text-xs text-slate-500">{{ \App\Support\WorkspaceProfile::roles()[$member->user->role] ?? ucfirst($member->user->role) }}
                            @if($member->user->branch) · {{ $member->user->branch->name }} @endif
                        </p>
                    </div>
                    @if($member->overdue_count > 0)
                    <span class="text-[10px] font-bold bg-red-100 text-red-700 px-2 py-0.5 rounded-full">{{ $member->overdue_count }} late</span>
                    @endif
                </div>
                <dl class="mt-2 grid grid-cols-2 gap-1 text-[10px] text-slate-600">
                    <div><dt class="inline">Open:</dt> <dd class="inline font-bold">{{ $member->open_count }}</dd></div>
                    <div><dt class="inline">This week:</dt> <dd class="inline font-bold">{{ $member->due_this_week }}</dd></div>
                    <div><dt class="inline">Planned:</dt> <dd class="inline font-bold">{{ $member->planned_hours }}h</dd></div>
                    <div><dt class="inline">Logged 30d:</dt> <dd class="inline font-bold">{{ $member->logged_hours_30d }}h</dd></div>
                </dl>
                <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden" title="Relative load">
                    @php $maxLoad = max(1, $members->max('load_score')); @endphp
                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ min(100, round(($member->load_score / $maxLoad) * 100)) }}%"></div>
                </div>
            </div>
            <div class="space-y-2 min-h-[120px] rounded-lg bg-slate-50/80 p-2 border border-dashed border-slate-200"
                @if($canReassign) @dragover.prevent="$el.classList.add('ring-2','ring-indigo-300')" @dragleave="$el.classList.remove('ring-2','ring-indigo-300')" @drop.prevent="dropOn({{ $member->user->id }}, $event)" @endif>
                @forelse($member->tasks as $task)
                @include('workload.partials.task-card', ['task' => $task, 'canReassign' => $canReassign])
                @empty
                <p class="text-xs text-slate-400 text-center py-6">No open tasks</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>

@if($canReassign)
<form id="workload-reassign-form" method="POST" action="{{ route('workload.reassign') }}" class="hidden">
    @csrf
    @method('PATCH')
    <input type="hidden" name="task_id" id="reassign-task-id">
    <input type="hidden" name="assigned_to" id="reassign-assignee-id">
    @if($branchId)<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
</form>

@push('scripts')
<script>
function workloadBoard() {
    return {
        draggingTaskId: null,
        dragStart(event, taskId) {
            this.draggingTaskId = taskId;
            event.dataTransfer.setData('text/plain', String(taskId));
            event.dataTransfer.effectAllowed = 'move';
        },
        dropOn(assigneeId, event) {
            event.currentTarget.classList.remove('ring-2', 'ring-indigo-300');
            if (!this.draggingTaskId) return;
            document.getElementById('reassign-task-id').value = this.draggingTaskId;
            document.getElementById('reassign-assignee-id').value = assigneeId;
            document.getElementById('workload-reassign-form').submit();
        }
    };
}
</script>
@endpush
@endif
@endsection
