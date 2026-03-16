@extends('layouts.app')

@section('header', 'Setup New Retainer')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-[3rem] shadow-2xl border border-slate-100 overflow-hidden">
        <div class="bg-slate-900 p-12 text-white relative overflow-hidden">
            <div class="absolute right-0 top-0 -mr-20 -mt-20 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <h3 class="text-3xl font-black italic tracking-tighter">Automate Revenue</h3>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-[0.3em] mt-3">Setup Recurring Billing Cycle</p>
            </div>
        </div>

        <form action="{{ route('subscriptions.store') }}" method="POST" class="p-12 space-y-10">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Target Client</label>
                        <select name="client_id" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800">
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Subscription Name</label>
                        <input type="text" name="name" required placeholder="e.g. Monthly GST Filing Retainer" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Recurring Amount (₹)</label>
                        <input type="number" name="amount" required step="0.01" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800">
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Billing Frequency</label>
                        <select name="frequency" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="semi-annually">Semi-Annually</option>
                            <option value="annually">Annually</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Billing Day of Month</label>
                        <input type="number" name="billing_day" required min="1" max="31" value="1" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Start Date</label>
                        <input type="date" name="start_date" required value="{{ date('Y-m-d') }}" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Internal Notes (Optional)</label>
                <textarea name="notes" rows="3" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-800"></textarea>
            </div>

            <div class="pt-6 flex justify-end space-x-4">
                <a href="{{ route('subscriptions.index') }}" class="px-8 py-5 text-slate-400 font-black uppercase text-[10px] tracking-widest hover:text-slate-900 transition-colors">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-12 py-5 rounded-2xl font-black uppercase text-sm tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-slate-900 transition-all">Activate Retainer</button>
            </div>
        </form>
    </div>
</div>
@endsection