@extends('layouts.app')

@section('header', 'Expense Management')

@section('content')
<div class="space-y-6">
    <!-- Summary Header -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 md:col-span-2">
            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-4">Total Expenses (Current Month)</h4>
            <div class="flex items-end justify-between">
                <div>
                    <span class="text-4xl font-extrabold text-gray-900">₹{{ number_format($totalMonthly, 2) }}</span>
                    <span class="ml-2 text-sm text-gray-500 font-medium tracking-tight">Across {{ $expenses->total() }} entries</span>
                </div>
                <div class="p-4 bg-red-50 rounded-2xl">
                    <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 flex flex-col justify-center">
            <p class="text-sm font-bold text-gray-500 uppercase">Top Category</p>
            @php
            $topCategory = collect($categorySummary)->sortByDesc('total')->first();
            @endphp
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $topCategory['category'] ?? 'N/A' }}</p>
            <p class="text-sm text-gray-500">₹{{ number_format($topCategory['total'] ?? 0, 0) }} spent</p>
        </div>

        <div class="bg-indigo-600 rounded-2xl shadow-lg p-6 border border-indigo-500 flex items-center justify-center">
            <a href="{{ route('expenses.create') }}" class="text-white font-bold text-lg flex items-center group text-center">
                <div class="bg-white/20 p-2 rounded-lg mr-3 group-hover:bg-white/30 transition-colors">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                Add Expense
            </a>
        </div>
    </div>

    <!-- Main List and Category Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- List -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">Recent Expenses</h3>
                <form action="{{ route('expenses.index') }}" method="GET" class="flex gap-2">
                    <select name="category" class="rounded-lg border-gray-200 text-xs py-1.5 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        @foreach(\App\Models\Expense::categories() as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-white border border-gray-200 p-1.5 rounded-lg text-gray-600 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Vendor / Desc</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($expenses as $expense)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $expense->expense_date->format('d M') }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ $expense->vendor ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500 truncate max-w-[150px]">{{ $expense->description }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-[10px] font-bold">{{ $expense->category }}</span>
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-900">₹{{ number_format($expense->amount, 2) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    @if($expense->receipt_path)
                                    <a href="{{ Storage::url($expense->receipt_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @endif
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="return confirm('Archive this expense?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">No expenses recorded for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4">
                {{ $expenses->links() }}
            </div>
        </div>

        <!-- Sidebar Category Stats -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="font-bold text-gray-800">By Category</h3>
                </div>
                <div class="p-6 space-y-4">
                    @forelse($categorySummary as $summary)
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="font-bold text-gray-700">{{ $summary['category'] }}</span>
                            <span class="font-bold text-gray-900">₹{{ number_format($summary['total'], 0) }}</span>
                        </div>
                        @php
                        $percent = $totalMonthly > 0 ? ($summary['total'] / $totalMonthly) * 100 : 0;
                        @endphp
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tight text-right">{{ round($percent) }}% OF TOTAL</div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 text-center py-4 italic">No categorical data</p>
                    @endforelse
                </div>
            </div>

            <!-- Hint -->
            <div class="bg-indigo-50 rounded-2xl p-6 border border-indigo-100 border-dashed">
                <div class="flex items-start">
                    <div class="flex-shrink-0 pt-0.5">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-indigo-900">Pro Tip</h3>
                        <p class="mt-1 text-xs text-indigo-700 leading-relaxed uppercase tracking-tighter">
                            Always upload a receipt image. It helps during tax audits and year-end reconciliation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection