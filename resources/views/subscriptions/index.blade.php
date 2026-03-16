@extends('layouts.app')

@section('header', 'Subscription Management')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">Recurring Retainers</h2>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Automated revenue & billing cycles</p>
        </div>
        <a href="{{ route('subscriptions.create') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-slate-900 transition-all">Setup Retainer</a>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                    <tr>
                        <th class="px-10 py-6">Client & Subscription</th>
                        <th class="px-10 py-6">Amount</th>
                        <th class="px-10 py-6">Frequency</th>
                        <th class="px-10 py-6">Next Billing</th>
                        <th class="px-10 py-6">Status</th>
                        <th class="px-10 py-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($subscriptions as $sub)
                    <tr class="hover:bg-indigo-50/20 transition-colors group">
                        <td class="px-10 py-7">
                            <div class="text-sm font-black text-slate-900">{{ $sub->client->name }}</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ $sub->name }}</div>
                        </td>
                        <td class="px-10 py-7">
                            <span class="text-sm font-black text-slate-900">₹ {{ number_format($sub->amount) }}</span>
                        </td>
                        <td class="px-10 py-7 text-xs font-black uppercase text-slate-500">
                            {{ $sub->frequency }}
                        </td>
                        <td class="px-10 py-7">
                            <div class="text-xs font-black text-slate-900">{{ $sub->next_billing_date->format('d M Y') }}</div>
                            <div class="text-[10px] font-bold text-slate-400">Day {{ $sub->billing_day }}</div>
                        </td>
                        <td class="px-10 py-7">
                            <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase border {{ $sub->status === 'active' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                                {{ $sub->status }}
                            </span>
                        </td>
                        <td class="px-10 py-7 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <form action="{{ route('subscriptions.toggle', $sub) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                        @if($sub->status === 'active')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        @else
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        </svg>
                                        @endif
                                    </button>
                                </form>
                                <form action="{{ route('subscriptions.destroy', $sub) }}" method="POST" onsubmit="return confirm('Cancel this subscription?')">
                                    @csrf @method('DELETE')
                                    <button class="p-2 text-slate-400 hover:text-red-600 transition-colors">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-10 py-20 text-center">
                            <div class="inline-flex items-center justify-center p-6 bg-slate-50 rounded-full mb-4">
                                <svg class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h4 class="text-slate-900 font-black">No Active Retainers</h4>
                            <p class="text-slate-500 text-xs font-bold uppercase mt-2 tracking-widest">Setup recurring billing to automate your revenue.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-10 py-6 border-t border-slate-50 bg-slate-50/30">
            {{ $subscriptions->links() }}
        </div>
    </div>
</div>
@endsection