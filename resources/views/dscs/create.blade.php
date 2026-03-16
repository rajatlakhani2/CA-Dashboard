@extends('layouts.app')

@section('header', 'Register DSC')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden animate-enter">
        <div class="bg-indigo-600 p-10 text-white relative overflow-hidden">
            <div class="absolute right-0 top-0 -mr-10 -mt-10 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <h3 class="text-2xl font-black italic tracking-tight">Security Protocol</h3>
                <p class="text-indigo-200 text-xs font-bold uppercase tracking-[0.2em] mt-2">New Certificate Enrollment</p>
            </div>
        </div>

        <form action="{{ route('dscs.store') }}" method="POST" class="p-10 space-y-10">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                <!-- Client Selection -->
                <div class="col-span-2">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Associated Client <span class="text-indigo-600">*</span></label>
                    <select name="client_id" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all py-4 px-6 font-bold text-slate-800 shadow-sm">
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }} ({{ $client->client_id }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Holder Name -->
                <div class="col-span-2">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Certificate Holder Name <span class="text-indigo-600">*</span></label>
                    <input type="text" name="holder_name" value="{{ old('holder_name') }}" required placeholder="Legal name on the certificate" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all py-4 px-6 font-bold text-slate-800 shadow-sm">
                </div>

                <!-- Class -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Token Class</label>
                    <select name="class_type" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all py-4 px-6 font-bold text-slate-800 shadow-sm">
                        <option value="Class 3">Class 3 (Latest / High Security)</option>
                        <option value="Class 2">Class 2 (Legacy)</option>
                        <option value="DGFT">DGFT</option>
                    </select>
                </div>

                <!-- Provider -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">CA Provider</label>
                    <input type="text" name="provider" value="{{ old('provider') }}" placeholder="e.g. eMudhra, Sify, Vsign" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all py-4 px-6 font-bold text-slate-800 shadow-sm">
                </div>

                <!-- Dates -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Issue Date <span class="text-indigo-600">*</span></label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all py-4 px-6 font-bold text-slate-800 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Expiry Date <span class="text-indigo-600">*</span></label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all py-4 px-6 font-bold text-slate-800 shadow-sm">
                </div>

                <!-- Notes -->
                <div class="col-span-2">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Storage Notes / Pin Details</label>
                    <textarea name="notes" rows="2" placeholder="Where is the token kept? Any specific pin details? (Encrypted storage recommended)" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:bg-white transition-all py-4 px-6 font-bold text-slate-800 shadow-sm">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-between pt-8">
                <a href="{{ route('dscs.index') }}" class="text-slate-400 hover:text-slate-900 font-black uppercase tracking-widest text-xs transition-colors px-6">Abort</a>
                <button type="submit" class="bg-slate-900 text-white px-12 py-5 rounded-2xl font-black uppercase tracking-[0.2em] text-sm hover:tracking-[0.3em] transition-all shadow-2xl hover:bg-indigo-600 transform active:scale-95">
                    Enroll Token
                </button>
            </div>
        </form>
    </div>
</div>
@endsection