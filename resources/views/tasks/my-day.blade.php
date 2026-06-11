@extends('layouts.app')

@section('header', 'My Day')

@section('content')
<div class="max-w-lg mx-auto space-y-6 pb-28 lg:pb-6">
    <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl p-6 text-white shadow-lg" data-demo-tour="my-day">
        <p class="text-indigo-200 text-xs font-bold uppercase tracking-widest">Mobile work mode</p>
        <h1 class="text-2xl font-black mt-1">{{ auth()->user()->name }}</h1>
        <p class="text-sm text-indigo-100 mt-2">{{ now()->format('l, d M Y') }}</p>
        <p class="text-sm mt-3">{{ $tasksToday->count() }} due / overdue · {{ $tasksUpcoming->count() }} upcoming</p>
    </div>

    <section>
        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Due today & overdue</h2>
        @forelse($tasksToday as $task)
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-3 shadow-sm" data-my-day-task-card x-data="{ showNote: false, showTime: false }">
            <div class="flex justify-between items-start gap-2">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900">{{ $task->title }}</p>
                    @if($task->client)<p class="text-xs text-gray-500 mt-1 truncate">{{ $task->client->name }}</p>@endif
                    <p class="text-xs mt-1 {{ $task->due_date && $task->due_date->isPast() ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                        Due {{ $task->due_date?->format('d M Y') ?? '—' }} · {{ $task->priority }}
                    </p>
                </div>
                <span class="shrink-0 text-[10px] font-bold uppercase px-2 py-1 rounded bg-gray-100 text-gray-600" data-my-day-status>{{ $task->status }}</span>
            </div>

            @if($task->description)
            <p class="mt-2 text-xs text-gray-600 whitespace-pre-line bg-slate-50 rounded-lg p-2 max-h-24 overflow-y-auto">{{ $task->description }}</p>
            @endif

            <div class="mt-3 flex gap-2">
                @if($task->status !== \App\Models\Task::STATUS_IN_PROGRESS)
                <form action="{{ route('tasks.update-status', $task) }}" method="POST" class="flex-1" data-my-day-status-form data-status-label="{{ \App\Models\Task::STATUS_IN_PROGRESS }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ \App\Models\Task::STATUS_IN_PROGRESS }}">
                    <button type="submit" class="w-full py-2.5 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 text-sm font-bold">Start</button>
                </form>
                @endif
                <form action="{{ route('tasks.update-status', $task) }}" method="POST" class="flex-1" data-my-day-status-form data-status-label="{{ \App\Models\Task::STATUS_COMPLETED }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ \App\Models\Task::STATUS_COMPLETED }}">
                    <button type="submit" class="w-full py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-bold">Done</button>
                </form>
            </div>

            <div class="mt-2 flex gap-3 text-xs font-semibold">
                <button type="button" @click="showNote = !showNote; showTime = false" class="text-indigo-600" x-text="showNote ? 'Hide note' : '+ Note'"></button>
                <button type="button" @click="showTime = !showTime; showNote = false" class="text-indigo-600" x-text="showTime ? 'Hide time' : '+ Log time'"></button>
            </div>

            <form x-show="showNote" x-cloak action="{{ route('tasks.mobile-note', $task) }}" method="POST" class="mt-2 space-y-2">
                @csrf
                @method('PATCH')
                <textarea name="note" rows="2" required placeholder="Quick note (saved on task)…" class="w-full rounded-lg border-gray-200 text-sm"></textarea>
                <button type="submit" class="w-full py-2 rounded-lg bg-slate-800 text-white text-xs font-bold">Save note</button>
            </form>

            <form x-show="showTime" x-cloak action="{{ route('tasks.mobile-time', $task) }}" method="POST" class="mt-2 space-y-2">
                @csrf
                <div class="flex gap-2">
                    @foreach([0.5, 1, 2] as $preset)
                    <label class="flex-1">
                        <input type="radio" name="hours" value="{{ $preset }}" class="sr-only peer" {{ $loop->first ? 'checked' : '' }}>
                        <span class="block text-center py-2 rounded-lg border border-gray-200 text-xs font-bold peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600">{{ $preset }}h</span>
                    </label>
                    @endforeach
                </div>
                <input type="hidden" name="date" value="{{ now()->toDateString() }}">
                <input type="text" name="description" maxlength="255" placeholder="What did you work on? (optional)" class="w-full rounded-lg border-gray-200 text-sm">
                <button type="submit" class="w-full py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold">Log time</button>
            </form>
        </div>
        @empty
        <p class="text-gray-500 text-sm bg-white rounded-xl p-6 border border-dashed">Nothing due today. Great work.</p>
        @endforelse
    </section>

    @if($tasksUpcoming->isNotEmpty())
    <section>
        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Coming up</h2>
        @foreach($tasksUpcoming as $task)
        <div class="bg-white rounded-xl border border-gray-100 p-4 mb-2">
            <p class="font-medium text-gray-800">{{ $task->title }}</p>
            <p class="text-xs text-gray-500 mt-1">Due {{ $task->due_date?->format('d M') }} · {{ $task->status }}</p>
            <a href="{{ route('tasks.edit', $task) }}" class="inline-block mt-2 text-xs text-indigo-600 font-medium">Open full editor</a>
        </div>
        @endforeach
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function toast(message) {
        if (window.DemoTourPlay?.toast) {
            window.DemoTourPlay.toast(message, 2200);
            return;
        }
        var el = document.getElementById('my-day-status-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'my-day-status-toast';
            el.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-50 max-w-sm px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold shadow-lg opacity-0 transition-opacity';
            document.body.appendChild(el);
        }
        el.textContent = message;
        el.style.opacity = '1';
        setTimeout(function () { el.style.opacity = '0'; }, 2200);
    }

    document.querySelectorAll('[data-my-day-status-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) return;
            btn.disabled = true;

            var card = form.closest('[data-my-day-task-card]');
            var statusEl = card?.querySelector('[data-my-day-status]');
            var statusLabel = form.getAttribute('data-status-label') || '';
            var body = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body,
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('Status update failed');
                    return res.json();
                })
                .then(function (data) {
                    if (statusEl && statusLabel) {
                        statusEl.textContent = statusLabel;
                    }
                    if (statusLabel === @json(\App\Models\Task::STATUS_IN_PROGRESS)) {
                        form.remove();
                    }
                    card?.classList.add('demo-tour-flash-pulse');
                    setTimeout(function () { card?.classList.remove('demo-tour-flash-pulse'); }, 1200);
                    toast(data.message || 'Task status updated.');
                })
                .catch(function () {
                    toast('Could not update task. Try again.');
                })
                .finally(function () {
                    btn.disabled = false;
                });
        });
    });
})();
</script>
@endpush
