@extends('layouts.app')

@section('header', 'TDS Management')

@section('content')
<div class="space-y-6">
    <!-- Analysis Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-gray-100 p-8 flex items-center justify-between">
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Total TDS Receivable</p>
                <h3 class="text-4xl font-black text-slate-900">₹{{ number_format($totalTds, 2) }}</h3>
                <p class="text-xs text-indigo-500 font-bold mt-2 uppercase">Tracking across registered invoices</p>
            </div>
            <div class="h-16 w-16 bg-slate-100 rounded-2xl flex items-center justify-center">
                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
            <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Certificates Pending</p>
            <div class="flex items-baseline space-x-2">
                <span class="text-3xl font-black text-orange-600">{{ $pendingCertificates }}</span>
                <span class="text-xs text-slate-500 font-black uppercase tracking-tighter">Invoices</span>
            </div>
            <div class="mt-4 w-full bg-slate-50 h-1.5 rounded-full overflow-hidden">
                @php
                $totalCount = $tdsEntries->count();
                $percentPending = $totalCount > 0 ? ($pendingCertificates / $totalCount) * 100 : 0;
                @endphp
                <div class="bg-orange-500 h-full rounded-full" style="width: {{ $percentPending }}%"></div>
            </div>
        </div>

        <div class="bg-slate-950 rounded-3xl shadow-xl p-8 flex flex-col justify-center">
            <h4 class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em]">Quick Entry</h4>
            <p class="text-white text-lg font-bold leading-tight mt-1">Manual TDS Sync</p>
            <button @click="$dispatch('open-tds-modal')" class="mt-4 bg-white text-slate-900 px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-500 hover:text-white transition-all">Add Record</button>
        </div>
    </div>

    <!-- TDS Ledger -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-10 py-8 border-b border-gray-50 flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-900 text-xl tracking-tight">TDS Repository</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-1">Status of Form 16A and TDS Deductions</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                    <tr>
                        <th class="px-10 py-6">Invoice Details</th>
                        <th class="px-10 py-6">Status</th>
                        <th class="px-10 py-6">Rate %</th>
                        <th class="px-10 py-6">Deducted Amount</th>
                        <th class="px-10 py-6">Certificate Info</th>
                        <th class="px-10 py-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($tdsEntries as $entry)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-10 py-7">
                            <div class="text-sm font-black text-slate-900">#{{ $entry->invoice->invoice_number }}</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ $entry->invoice->client->name }}</div>
                        </td>
                        <td class="px-10 py-7">
                            @if($entry->certificate_received)
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase bg-green-50 text-green-700 border border-green-100 shadow-sm">Received</span>
                            @else
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase bg-orange-50 text-orange-700 border border-orange-100 shadow-sm">Pending</span>
                            @endif
                        </td>
                        <td class="px-10 py-7 text-sm font-bold text-slate-600">
                            {{ number_format($entry->tds_rate, 1) }}%
                        </td>
                        <td class="px-10 py-7 text-sm font-black text-slate-900">
                            ₹{{ number_format($entry->tds_amount, 2) }}
                        </td>
                        <td class="px-10 py-7">
                            @if($entry->certificate_number)
                            <div class="text-xs font-bold text-slate-900">{{ $entry->certificate_number }}</div>
                            <div class="text-[10px] text-slate-400 font-medium">{{ $entry->certificate_date ? $entry->certificate_date->format('d M, Y') : '' }}</div>
                            @else
                            <span class="text-[10px] text-slate-300 italic">No certificate uploaded</span>
                            @endif
                        </td>
                        <td class="px-10 py-7 text-right">
                            <div class="flex items-center justify-end space-x-3">
                                <button class="text-slate-400 hover:text-indigo-600 transition-colors" title="Edit Entry">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <form action="{{ route('tds.destroy', $entry) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-600 transition-colors">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-10 py-24 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-slate-50 h-20 w-20 rounded-full flex items-center justify-center mb-6">
                                    <svg class="h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h4 class="text-slate-900 font-black">Archive is Empty</h4>
                                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">No TDS entries found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection