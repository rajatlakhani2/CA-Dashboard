@extends('layouts.app')

@section('header', 'Client Onboarding')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- Onboarding Header -->
    <div class="bg-gradient-to-br from-indigo-700 to-indigo-900 rounded-[3rem] p-12 shadow-2xl relative overflow-hidden text-white">
        <div class="absolute right-0 top-0 -mr-24 -mt-24 w-96 h-96 bg-white/10 rounded-full blur-[100px]"></div>

        <div class="relative z-10">
            <div class="flex items-center space-x-4 mb-6">
                <div class="h-16 w-16 bg-white/20 backdrop-blur-md rounded-3xl flex items-center justify-center border border-white/30">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-black">{{ $client->name }}</h2>
                    <p class="text-indigo-200 text-xs font-bold uppercase tracking-[0.2em] mt-1">Onboarding Protocol Phase</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-10 border-t border-white/10 pt-8">
                <div class="md:col-span-2">
                    <p class="text-[10px] font-black uppercase tracking-widest text-indigo-300 mb-3">Overall Readiness</p>
                    @php
                    $completedCount = $checklist->where('is_completed', true)->count();
                    $totalCount = $checklist->count();
                    $readiness = $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0;
                    @endphp
                    <div class="w-full bg-white/10 h-3 rounded-full overflow-hidden border border-white/5">
                        <div class="bg-white h-full transition-all duration-1000 shadow-[0_0_20px_rgba(255,255,255,0.5)]" style="width: {{ $readiness }}%"></div>
                    </div>
                </div>
                <div class="w-full bg-white/10 h-3 rounded-full overflow-hidden border border-white/5">
                    <div class="bg-white h-full transition-all duration-1000 shadow-[0_0_20px_rgba(255,255,255,0.5)]" style="width: {{ $readiness }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checklist -->
<div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-10 py-8 border-b border-gray-50 flex items-center justify-between" x-data="{ open: false }">
        <h3 class="font-black text-slate-900 text-xl">Onboarding Milestones</h3>
        <button @click="open = true" class="bg-slate-50 text-slate-500 hover:bg-indigo-600 hover:text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Add Custom Milestone</button>

        <!-- Modal -->
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
            <div @click.away="open = false" class="bg-white rounded-[2rem] w-full max-w-md p-10 shadow-2xl">
                <h3 class="text-xl font-black text-slate-900 mb-6">Add Custom Milestone</h3>
                <form action="{{ route('onboarding.add-item', $client) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <input type="text" name="item" required placeholder="e.g. Collect MSME Certificate" class="w-full bg-slate-50 border-0 rounded-2xl py-4 px-6 font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500">
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-indigo-100 hover:bg-slate-900 transition-all">Add to Checklist</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="p-4">
        @forelse($checklist as $item)
        <div class="group flex items-center justify-between p-6 rounded-[1.5rem] hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100">
            <div class="flex items-center space-x-6">
                <form action="{{ route('onboarding.toggle', $item) }}" method="POST">
                    @csrf
                    <button type="submit" class="h-8 w-8 rounded-xl border-2 transition-all flex items-center justify-center {{ $item->is_completed ? 'bg-green-500 border-green-500 text-white rotate-0' : 'bg-white border-slate-200 text-transparent hover:border-indigo-400 rotate-90' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </form>
                <div>
                    <p class="text-sm font-black {{ $item->is_completed ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $item->item }}</p>
                    @if($item->is_completed)
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Verified by {{ $item->user->name ?? 'System' }} • {{ $item->completed_at->format('d M, Y') }}</p>
                    @endif
                </div>
            </div>

            @if(!$item->is_completed)
            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                <span class="text-[10px] font-black uppercase text-indigo-600 tracking-widest">Pending Verification</span>
            </div>
            @endif
        </div>
        @empty
        <div class="py-20 text-center">
            <p class="text-slate-400 font-bold text-sm">No milestones defined. Start by adding one!</p>
        </div>
        @endforelse
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <a href="{{ route('clients.show', $client) }}" class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm flex items-center justify-between group">
        <div>
            <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">Back to Profile</h4>
            <p class="text-lg font-bold text-slate-800 mt-1">Client 360° View</p>
        </div>
        <div class="h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center group-hover:bg-slate-900 group-hover:text-white transition-all">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </div>
    </a>

    <div class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm flex items-center justify-between group">
        <div>
            <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">Finalize</h4>
            <p class="text-lg font-bold text-slate-800 mt-1">Mark as Operational</p>
        </div>
        <div class="h-12 w-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center group-hover:bg-green-600 group-hover:text-white transition-all opacity-50 cursor-not-allowed">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
    </div>
</div>
</div>
@endsection