@php
    $filters = $calendarFilters ?? new \App\Services\DashboardCalendarFilters();
    $opts = $calendarFilterOptions ?? ['services' => collect(), 'assignees' => collect(), 'branches' => collect(), 'categories' => ['A','B','C']];
@endphp
<div class="mb-4 p-4 rounded-xl bg-slate-50 border border-slate-200" x-data="calendarFilterBar()">
    <p class="text-xs font-bold text-slate-600 uppercase tracking-wide mb-3">Calendar filters</p>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 text-sm">
        <label class="flex items-center gap-2 col-span-2 md:col-span-1">
            <input type="checkbox" x-model="showTasks" @change="apply()" class="rounded border-slate-300 text-indigo-600">
            <span class="text-slate-700">Tasks</span>
        </label>
        <label class="flex items-center gap-2 col-span-2 md:col-span-1">
            <input type="checkbox" x-model="showDues" @change="apply()" class="rounded border-slate-300 text-indigo-600">
            <span class="text-slate-700">Service dues</span>
        </label>
        <div>
            <label class="block text-[10px] font-medium text-slate-500 mb-1">Status</label>
            <select x-model="dueStatus" @change="apply()" class="w-full rounded-md border-slate-300 text-xs py-1.5">
                <option value="active">Pending + overdue</option>
                <option value="pending">Pending only</option>
                <option value="overdue">Overdue only</option>
                <option value="completed">Completed</option>
                <option value="all">All statuses</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-medium text-slate-500 mb-1">Service</label>
            <select x-model="serviceId" @change="apply()" class="w-full rounded-md border-slate-300 text-xs py-1.5">
                <option value="">All services</option>
                @foreach($opts['services'] as $service)
                <option value="{{ $service->id }}" @selected($filters->serviceId === $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-medium text-slate-500 mb-1">Assignee / manager</label>
            <select x-model="assignedTo" @change="apply()" class="w-full rounded-md border-slate-300 text-xs py-1.5">
                <option value="">Everyone</option>
                @foreach($opts['assignees'] as $person)
                <option value="{{ $person->id }}" @selected($filters->assignedTo === $person->id)>{{ $person->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-medium text-slate-500 mb-1">Client category</label>
            <select x-model="category" @change="apply()" class="w-full rounded-md border-slate-300 text-xs py-1.5">
                <option value="">All</option>
                @foreach($opts['categories'] as $cat)
                <option value="{{ $cat }}" @selected($filters->category === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        @if($opts['branches']->isNotEmpty())
        <div>
            <label class="block text-[10px] font-medium text-slate-500 mb-1">Branch</label>
            <select x-model="branchId" @change="apply()" class="w-full rounded-md border-slate-300 text-xs py-1.5">
                <option value="">All branches</option>
                @foreach($opts['branches'] as $branch)
                <option value="{{ $branch->id }}" @selected($filters->branchId === $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>
    <button type="button" @click="reset()" class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Reset filters</button>
</div>
