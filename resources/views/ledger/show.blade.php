@extends('layouts.app')

@section('header', 'Client Ledger / SOA')

@section('content')
<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-slate-900 rounded-[2.5rem] p-10 shadow-2xl relative overflow-hidden">
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-[80px]"></div>
        <div class="absolute left-0 bottom-0 -ml-10 -mb-10 w-40 h-40 bg-purple-500/10 rounded-full blur-[40px]"></div>

        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
            <div>
                <div class="flex items-center space-x-3 mb-4">
                    <span class="bg-indigo-500/20 text-indigo-400 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-500/30">Financial Record</span>
                    <span class="text-slate-500 text-[10px] font-black uppercase tracking-tighter">ID: {{ $client->client_id }}</span>
                </div>
                <h2 class="text-3xl font-black text-white leading-none">{{ $client->name }}</h2>
                <p class="text-slate-400 text-sm mt-3 font-medium tracking-wide">Detailed statement of accounts and aging analysis</p>
            </div>

            <div class="flex flex-col items-end">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Current Outstanding</p>
                <p class="text-4xl font-black text-white">₹{{ number_format($totalOutstanding, 2) }}</p>
                <a href="{{ route('ledger.soa', $client) }}" class="mt-6 inline-flex items-center px-8 py-3 bg-white text-slate-900 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-500 hover:text-white transition-all shadow-xl shadow-indigo-500/10 group">
                    <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Download SOA
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Aging Analysis -->
        <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
            <h3 class="font-black text-slate-900 uppercase tracking-widest text-xs mb-8 border-b border-slate-50 pb-4">Aging Analysis</h3>
            <div class="space-y-6">
                @foreach($aging as $label => $amount)
                <div class="group">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-black uppercase tracking-tighter text-slate-400 group-hover:text-indigo-600 transition-colors">{{ $label }}</span>
                        <span class="text-sm font-black text-slate-900">₹{{ number_format($amount, 0) }}</span>
                    </div>
                    <div class="w-full bg-slate-50 h-1 rounded-full overflow-hidden">
                        @php
                        $p = $totalOutstanding > 0 ? ($amount / $totalOutstanding) * 100 : 0;
                        @endphp
                        <div class="h-full rounded-full transition-all duration-1000 {{ 
                            $label == '0-30 Days' ? 'bg-indigo-400' : 
                            ($label == '31-60 Days' ? 'bg-indigo-600' : 
                            ($label == '61-90 Days' ? 'bg-purple-600' : 'bg-red-500'))
                        }}" style="width: {{ $p }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-10 p-4 bg-slate-50 rounded-2xl border border-slate-100 italic">
                <p class="text-[10px] text-slate-500 leading-relaxed font-medium">Aging is calculated based on invoice date relative to today's date.</p>
            </div>
        </div>

        <!-- Transaction Ledger -->
        <div class="lg:col-span-2 bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/20">
                <h3 class="font-black text-slate-900 uppercase tracking-widest text-xs">Transaction Ledger</h3>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Live Statements</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        <tr>
                            <th class="px-8 py-5">Date</th>
                            <th class="px-8 py-5">Voucher / Narration</th>
                            <th class="px-8 py-5 text-right">Debit (+)</th>
                            <th class="px-8 py-5 text-right">Credit (-)</th>
                            <th class="px-8 py-5 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($ledger as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-5 text-[11px] font-bold text-slate-500">{{ \Carbon\Carbon::parse($item['date'])->format('d M, Y') }}</td>
                            <td class="px-8 py-5">
                                <div class="text-[11px] font-black text-slate-900">{{ $item['voucher'] }}</div>
                                <div class="text-[10px] text-slate-400 font-medium truncate max-w-[200px]">{{ $item['description'] }}</div>
                            </td>
                            <td class="px-8 py-5 text-right text-[11px] font-black text-slate-900">
                                {{ $item['type'] == 'Invoice' ? '₹' . number_format($item['amount'], 2) : '-' }}
                            </td>
                            <td class="px-8 py-5 text-right text-[11px] font-black text-green-600">
                                {{ $item['type'] == 'Payment' ? '₹' . number_format($item['amount'], 2) : '-' }}
                            </td>
                            <td class="px-8 py-5 text-right text-[11px] font-black text-slate-950 bg-slate-50/50">
                                ₹{{ number_format($item['balance'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center text-slate-400 italic">No transaction history found for this client.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection