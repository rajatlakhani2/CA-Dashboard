@extends('layouts.app')

@section('header')
Staff Productivity Report
@endsection

@section('content')
<div class="space-y-6">
    @include('reports.partials.filters')
    @include('reports.partials.tabs')

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white shadow rounded-lg p-5">
            <p class="text-xs font-bold text-slate-500 uppercase">Tasks completed</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ $totals['completed'] }}</p>
            <p class="text-xs text-slate-500 mt-1">In selected period</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <p class="text-xs font-bold text-slate-500 uppercase">Hours logged</p>
            <p class="text-3xl font-black text-indigo-600 mt-1">{{ $totals['hours'] }}h</p>
            <p class="text-xs text-slate-500 mt-1">{{ $totals['billable_hours'] }}h billable</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <p class="text-xs font-bold text-slate-500 uppercase">Team members</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ $rows->count() }}</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full text-sm divide-y divide-slate-100">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Team member</th>
                    <th class="px-4 py-3 text-right">Completed</th>
                    <th class="px-4 py-3 text-right">Open</th>
                    <th class="px-4 py-3 text-right">Overdue open</th>
                    <th class="px-4 py-3 text-right">On-time %</th>
                    <th class="px-4 py-3 text-right">Avg delay (late)</th>
                    <th class="px-4 py-3 text-right">Hours</th>
                    <th class="px-4 py-3 text-right">Billable</th>
                    <th class="px-4 py-3 text-right">Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <span class="font-semibold text-slate-900">{{ $row->user->name }}</span>
                        <span class="text-xs text-slate-500 capitalize block">{{ $row->user->role }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium">{{ $row->completed_count }}</td>
                    <td class="px-4 py-3 text-right">{{ $row->open_count }}</td>
                    <td class="px-4 py-3 text-right {{ $row->overdue_open ? 'text-red-600 font-bold' : '' }}">{{ $row->overdue_open }}</td>
                    <td class="px-4 py-3 text-right">{{ $row->on_time_rate }}%</td>
                    <td class="px-4 py-3 text-right">{{ $row->avg_delay_days }}d</td>
                    <td class="px-4 py-3 text-right">{{ $row->total_hours }}h</td>
                    <td class="px-4 py-3 text-right text-emerald-700">{{ $row->billable_hours }}h</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold {{ $row->productivity_score >= 60 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $row->productivity_score }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-slate-500">No staff activity in this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-500">Score blends completions, on-time rate, and overdue open tasks. Delay averages days late for tasks finished after due date.</p>
</div>
@endsection
