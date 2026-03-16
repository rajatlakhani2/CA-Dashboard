@extends('layouts.app')

@section('header', 'Add Expense')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden animate-enter">
        <div class="bg-slate-900 px-8 py-8 flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-white">Record Outflow</h3>
                <p class="text-slate-400 text-xs mt-1 uppercase tracking-widest font-bold">New Expense Entry</p>
            </div>
            <div class="h-12 w-12 bg-indigo-500/20 rounded-2xl flex items-center justify-center border border-indigo-500/30">
                <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <!-- Category -->
                <div class="col-span-1">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Category <span class="text-indigo-500">*</span></label>
                    <select name="category" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all py-3 px-4 font-bold text-gray-800">
                        <option value="">-- Select --</option>
                        @foreach(\App\Models\Expense::categories() as $cat)
                        <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date -->
                <div class="col-span-1">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Transaction Date <span class="text-indigo-500">*</span></label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all py-3 px-4 font-bold text-gray-800">
                </div>

                <!-- Amount -->
                <div class="col-span-1">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Amount (INR) <span class="text-indigo-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required placeholder="0.00" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all py-3 px-4 font-black text-lg text-gray-900 placeholder-gray-300">
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">₹</div>
                    </div>
                </div>

                <!-- Payment Mode -->
                <div class="col-span-1">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Paid Via</label>
                    <select name="payment_mode" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all py-3 px-4 font-bold text-gray-800">
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="UPI">UPI / GPay</option>
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                    </select>
                </div>

                <!-- Vendor -->
                <div class="col-span-2">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Vendor / Paid To</label>
                    <input type="text" name="vendor" value="{{ old('vendor') }}" placeholder="e.g. Amazon Cloud, Office Landlord, DTDC" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all py-3 px-4 font-bold text-gray-800">
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Description <span class="text-indigo-500">*</span></label>
                    <input type="text" name="description" value="{{ old('description') }}" required placeholder="Short description of this expense" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all py-3 px-4 font-bold text-gray-800">
                </div>

                <!-- Receipt Upload -->
                <div class="col-span-2">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Attachment (Bill/Receipt)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-100 border-dashed rounded-3xl bg-gray-50 hover:bg-white hover:border-indigo-300 transition-all cursor-pointer group relative">
                        <input type="file" name="receipt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="space-y-2 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-indigo-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="text-sm text-gray-600">
                                <span class="font-bold text-indigo-600">Upload a file</span> or drag and drop
                            </div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-tighter">PNG, JPG, PDF up to 2MB</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4">
                <a href="{{ route('expenses.index') }}" class="text-gray-400 hover:text-gray-900 font-black uppercase tracking-widest text-xs transition-colors px-4 py-2">Back</a>
                <button type="submit" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-slate-900 transition-all shadow-xl shadow-indigo-100 transform active:scale-95">
                    Save Record
                </button>
            </div>
        </form>
    </div>
</div>
@endsection