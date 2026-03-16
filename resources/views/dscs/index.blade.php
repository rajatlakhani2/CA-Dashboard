@extends('layouts.app')

@section('header', 'DSC Tracker')

@section('content')
<div class="space-y-6">
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl shadow-sm p-8 border-l-8 border-indigo-500 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:rotate-12 transition-transform">
                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.9L10 1.55l7.834 3.35a1 1 0 01.61.92v5.335c0 5.253-3.047 10.126-7.834 12.845a1 1 0 01-.61 0c-4.787-2.719-7.834-7.592-7.834-12.845V5.82a1 1 0 01.61-.92zM9 7a1 1 0 112 0v4a1 1 0 11-2 0V7zm1 11a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Active DSCs</p>
            <p class="text-4xl font-black text-gray-900 mt-2">{{ $dscs->where('status', 'Active')->count() }}</p>
        </div>

        <div class="bg-white rounded-3xl shadow-sm p-8 border-l-8 border-orange-500 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:rotate-12 transition-transform">
                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Expiring Soon</p>
            <p class="text-4xl font-black text-orange-600 mt-2">{{ $expiringSoonCount }}</p>
        </div>

        <div class="bg-slate-900 rounded-3xl shadow-xl p-8 flex flex-col justify-center">
            <a href="{{ route('dscs.create') }}" class="flex items-center justify-between text-white group">
                <div>
                    <p class="text-sm font-bold text-indigo-400 uppercase tracking-tight">New Token</p>
                    <p class="text-xl font-black">Register DSC</p>
                </div>
                <div class="h-14 w-14 bg-indigo-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg shadow-indigo-500/40">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
            </a>
        </div>
    </div>

    <!-- Inventory -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
            <div>
                <h3 class="font-black text-gray-800 text-lg">Certificate Inventory</h3>
                <p class="text-xs text-gray-500 uppercase font-bold tracking-widest mt-1">Tracking {{ $dscs->total() }} digital signatures</p>
            </div>
            <form action="{{ route('dscs.index') }}" method="GET" class="flex gap-2">
                <select name="status" class="rounded-xl border-gray-200 text-xs font-bold py-2 focus:ring-indigo-500">
                    <option value="">Status: All</option>
                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Expired" {{ request('status') == 'Expired' ? 'selected' : '' }}>Expired</option>
                </select>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search client..." class="rounded-xl border-gray-200 text-xs font-bold py-2 focus:ring-indigo-500">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-900 transition-colors">Find</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                    <tr>
                        <th class="px-8 py-5">Holder / Client</th>
                        <th class="px-8 py-5">Class / Provider</th>
                        <th class="px-8 py-5">Valid Until</th>
                        <th class="px-8 py-5 text-center">Status</th>
                        <th class="px-8 py-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($dscs as $dsc)
                    <tr class="hover:bg-indigo-50/20 transition-colors">
                        <td class="px-8 py-6">
                            <div class="text-sm font-black text-slate-900">{{ $dsc->holder_name }}</div>
                            <div class="text-xs font-bold text-slate-400">{{ $dsc->client->name }}</div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center text-xs font-bold text-slate-700">
                                <span class="bg-slate-100 px-2 py-0.5 rounded mr-2">{{ $dsc->class_type }}</span>
                                {{ $dsc->provider ?? 'eMudhra' }}
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="text-sm font-bold {{ $dsc->expiry_date->isPast() ? 'text-red-600' : ($dsc->isExpiringSoon() ? 'text-orange-600' : 'text-slate-900') }}">
                                {{ $dsc->expiry_date->format('d M, Y') }}
                            </div>
                            <div class="text-[10px] uppercase font-black text-slate-400 mt-1">
                                Issued: {{ $dsc->issue_date->format('M Y') }}
                            </div>
                        </td>
                        <td class="px-8 py-6 text-center">
                            @if($dsc->status === 'Active')
                            @if($dsc->isExpiringSoon())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase bg-orange-100 text-orange-700 border border-orange-200">Expiring Soon</span>
                            @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase bg-green-100 text-green-700 border border-green-200">Protected</span>
                            @endif
                            @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-700 border border-red-200">{{ $dsc->status }}</span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end items-center space-x-2">
                                <a href="{{ route('dscs.edit', $dsc) }}" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('dscs.destroy', $dsc) }}" method="POST" onsubmit="return confirm('Deregister this DSC?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-slate-50 p-4 rounded-full mb-4">
                                    <svg class="w-12 h-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h4 class="text-slate-900 font-black">Secure Vault is Empty</h4>
                                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">No digital signature certificates found.</p>
                                <a href="{{ route('dscs.create') }}" class="mt-6 bg-indigo-600 text-white px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-900 transition-all shadow-lg shadow-indigo-100">Add First DSC</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-8 py-6 bg-slate-50/50">
            {{ $dscs->links() }}
        </div>
    </div>
</div>
@endsection