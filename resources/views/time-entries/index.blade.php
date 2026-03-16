@extends('layouts.app')

@section('header', 'Time Tracking')

@section('content')
<div class="space-y-6">
    <!-- Team Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-slate-100 flex items-center space-x-4">
            <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Total Hours Logged</p>
                <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($timeEntries->sum('hours'), 1) }}</p>
            </div>
        </div>

        <div class="md:col-span-2 bg-indigo-600 rounded-3xl shadow-lg p-6 flex items-center justify-between text-white group">
            <div class="flex items-center space-x-4">
                <div class="h-12 w-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-black text-white italic">Activity Stream</h4>
                    <p class="text-indigo-200 text-xs font-bold uppercase tracking-tighter">Logging productivity across {{ $tasks->count() }} active tasks</p>
                </div>
            </div>
            <button @click="$dispatch('open-time-modal')" class="bg-white text-indigo-600 px-6 py-2.5 rounded-xl font-black uppercase text-[10px] tracking-widest hover:bg-slate-900 hover:text-white transition-all transform active:scale-95">Log Time</button>
        </div>

        <div class="bg-slate-900 rounded-3xl shadow-xl p-6 flex flex-col justify-center items-center">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Retention Rate</p>
            <p class="text-2xl font-black text-white">98%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Time Log -->
        <div class="lg:col-span-2 bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-50 bg-slate-50/30 flex items-center justify-between">
                <h3 class="font-black text-slate-800">Productivity Log</h3>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-1 rounded bg-green-50 text-green-700 text-[10px] font-bold">LIVE SYNC</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        <tr>
                            <th class="px-8 py-5">Date</th>
                            <th class="px-8 py-5">User / Task</th>
                            <th class="px-8 py-5">Duration</th>
                            <th class="px-8 py-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($timeEntries as $entry)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6 text-xs font-bold text-slate-500">{{ $entry->date->format('d M, Y') }}</td>
                            <td class="px-8 py-6">
                                <div class="text-xs font-black text-slate-900">{{ $entry->user->name }}</div>
                                <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-tighter mt-1">{{ $entry->task->title ?? 'General Task' }}</div>
                                @if($entry->description)
                                <p class="text-[10px] text-slate-400 mt-1 italic">{{ $entry->description }}</p>
                                @endif
                            </td>
                            <td class="px-8 py-6 uppercase">
                                <span class="bg-indigo-50 text-indigo-700 font-black px-2.5 py-1 rounded-lg text-xs">{{ number_format($entry->hours, 1) }} HRS</span>
                                @if($entry->is_billable)
                                <span class="ml-1 text-[8px] font-black text-green-500 border border-green-200 px-1 rounded">BILLABLE</span>
                                @endif
                            </td>
                            <td class="px-8 py-6 text-right">
                                <form action="{{ route('time-entries.destroy', $entry) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="text-slate-300 hover:text-red-500 transition-colors">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center text-slate-400 font-bold italic">No time records found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-8 py-4 bg-slate-50/30">
                {{ $timeEntries->links() }}
            </div>
        </div>

        <!-- Log Form (Sidebar) -->
        <div class="bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-800">
            <h3 class="font-black text-white uppercase tracking-widest text-xs mb-8 border-b border-white/5 pb-4">Log Quick Entry</h3>

            <form action="{{ route('time-entries.store') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Target Task</label>
                    <select name="task_id" required class="w-full bg-white/5 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white/10 transition-all py-3 px-4 font-bold text-white shadow-sm">
                        <option value="" class="text-slate-900">-- Select Task --</option>
                        @foreach($tasks as $task)
                        <option value="{{ $task->id }}" class="text-slate-900">{{ $task->title }} ({{ $task->client->name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Hours</label>
                        <input type="number" step="0.5" name="hours" required placeholder="1.5" class="w-full bg-white/5 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white/10 transition-all py-3 px-4 font-bold text-white shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Date</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full bg-white/5 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white/10 transition-all py-3 px-4 font-bold text-white shadow-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Narration</label>
                    <textarea name="description" rows="2" placeholder="Brief of work done..." class="w-full bg-white/5 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white/10 transition-all py-3 px-4 font-bold text-white shadow-sm"></textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_billable" value="1" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-white/10 bg-white/5 rounded">
                    <label class="ml-2 block text-[10px] font-black text-slate-400 uppercase tracking-widest">Billable to Client</label>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white rounded-2xl py-4 font-black uppercase text-xs tracking-[0.2em] transition-all hover:bg-white hover:text-indigo-600 shadow-xl shadow-indigo-600/10 active:scale-95">Record Entry</button>
            </form>
        </div>
    </div>
</div>
@endsection