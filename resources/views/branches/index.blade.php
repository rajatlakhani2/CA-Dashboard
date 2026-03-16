@extends('layouts.app')

@section('header', 'Branch Management')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">Registered Branches</h2>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Multi-location operational control</p>
        </div>
        <button onclick="document.getElementById('addBranchModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-slate-900 transition-all">Add Branch Office</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @forelse($branches as $branch)
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden group hover:shadow-xl transition-all">
            <div class="h-2 bg-indigo-500"></div>
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <span class="bg-slate-50 text-slate-400 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">{{ $branch->code }}</span>
                    <span class="h-2 w-2 rounded-full {{ $branch->is_active ? 'bg-green-500 ring-4 ring-green-100' : 'bg-slate-300' }}"></span>
                </div>
                <h3 class="text-xl font-black text-slate-900">{{ $branch->name }}</h3>
                <p class="text-slate-500 text-sm mt-4 font-medium leading-relaxed">{{ $branch->address }}</p>

                <div class="mt-8 pt-6 border-t border-slate-50 flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">GSTIN</span>
                        <span class="text-xs font-bold text-slate-900">{{ $branch->gstin ?? 'N/A' }}</span>
                    </div>
                    <div class="flex space-x-2">
                        <button class="p-2 text-slate-300 hover:text-indigo-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="md:col-span-3 py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-200 flex flex-col items-center justify-center">
            <div class="bg-slate-50 p-6 rounded-full mb-6">
                <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h4 class="text-slate-900 font-black">Headquarters Only</h4>
            <p class="text-slate-500 text-xs font-bold uppercase mt-2 tracking-widest">No additional branches registered.</p>
        </div>
        @endforelse
    </div>

    <!-- Simple Add Modal Placeholder -->
    <div id="addBranchModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('addBranchModal').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-[2.5rem] shadow-2xl max-w-lg w-full p-10 overflow-hidden">
                <div class="absolute right-0 top-0 -mr-16 -mt-16 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl"></div>
                <h3 class="text-2xl font-black text-slate-900 mb-8 relative z-10">Add Branch</h3>
                <form action="{{ route('branches.store') }}" method="POST" class="space-y-6 relative z-10">
                    @csrf
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Branch Name</label>
                        <input type="text" name="name" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Branch Code</label>
                            <input type="text" name="code" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800" placeholder="MUM-01">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">GSTIN (Optional)</label>
                            <input type="text" name="gstin" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Address</label>
                        <textarea name="address" rows="3" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-slate-900 text-white rounded-2xl py-5 font-black uppercase text-sm tracking-widest shadow-xl shadow-slate-200 hover:bg-indigo-600 transition-all">Submit Details</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection