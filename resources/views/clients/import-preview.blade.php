@extends('layouts.app')

@section('header', 'Import Preview')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 text-sm text-indigo-900 space-y-2">
        <p>Review all rows before confirming. Invalid rows must be fixed in Excel and re-uploaded. Warnings can be reviewed but do not block import.</p>
        <p><strong>Services column:</strong> comma-separated names (e.g. <code class="bg-white/80 px-1 rounded">IT Return, GST Return</code>). Aliases like <code class="bg-white/80 px-1 rounded">Income Tax</code> or <code class="bg-white/80 px-1 rounded">ITR</code> also work. Leave blank to skip service assignment on that row.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase font-bold">New clients</p>
            <p class="text-2xl font-bold">{{ count($preview['create']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase font-bold">Updates</p>
            <p class="text-2xl font-bold">{{ count($preview['update']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-amber-500">
            <p class="text-xs text-gray-500 uppercase font-bold">Warnings</p>
            <p class="text-2xl font-bold">{{ count($preview['warnings'] ?? []) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase font-bold">Invalid rows</p>
            <p class="text-2xl font-bold">{{ count($preview['invalid']) }}</p>
        </div>
    </div>

    @if(!empty($preview['create']))
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 bg-green-50 font-semibold text-green-800">Rows to create (showing up to 25)</div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2 text-left">Row</th>
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">PAN</th>
                    <th class="px-4 py-2 text-left">Code</th>
                    <th class="px-4 py-2 text-left">Services</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(array_slice($preview['create'], 0, 25) as $row)
                <tr>
                    <td class="px-4 py-2">{{ $row['row'] }}</td>
                    <td class="px-4 py-2 font-medium">{{ $row['name'] }}</td>
                    <td class="px-4 py-2">{{ $row['pan'] }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $row['client_code'] ?? 'auto' }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ !empty($row['services_resolved']) ? implode(', ', $row['services_resolved']) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($preview['update']))
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 bg-blue-50 font-semibold text-blue-800">Rows to update (showing up to 25)</div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2 text-left">Row</th>
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">PAN</th>
                    <th class="px-4 py-2 text-left">Existing</th>
                    <th class="px-4 py-2 text-left">Services</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(array_slice($preview['update'], 0, 25) as $row)
                <tr>
                    <td class="px-4 py-2">{{ $row['row'] }}</td>
                    <td class="px-4 py-2 font-medium">{{ $row['name'] }}</td>
                    <td class="px-4 py-2">{{ $row['pan'] }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $row['existing_name'] ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ !empty($row['services_resolved']) ? implode(', ', $row['services_resolved']) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($preview['warnings']))
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 bg-amber-50 font-semibold text-amber-800">Warnings</div>
        <table class="min-w-full text-sm">
            <thead><tr class="bg-gray-50 text-xs uppercase"><th class="px-4 py-2 text-left">Row</th><th class="px-4 py-2">PAN</th><th class="px-4 py-2">Messages</th></tr></thead>
            <tbody>
                @foreach($preview['warnings'] as $row)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $row['row'] }}</td>
                    <td class="px-4 py-2">{{ $row['pan'] }}</td>
                    <td class="px-4 py-2 text-amber-800">{{ implode('; ', $row['messages']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($preview['invalid']))
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 bg-red-50 font-semibold text-red-800">Invalid rows — fix and re-upload</div>
        <table class="min-w-full text-sm">
            <thead><tr class="bg-gray-50"><th class="px-4 py-2 text-left">Row</th><th class="px-4 py-2">Name</th><th class="px-4 py-2">PAN</th><th class="px-4 py-2">Errors</th></tr></thead>
            <tbody>
                @foreach($preview['invalid'] as $row)
                <tr class="border-t"><td class="px-4 py-2">{{ $row['row'] }}</td><td class="px-4 py-2">{{ $row['name'] }}</td><td class="px-4 py-2">{{ $row['pan'] }}</td><td class="px-4 py-2 text-red-600">{{ implode('; ', $row['errors']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 flex flex-wrap gap-3 items-center">
        @if($canConfirm ?? false)
        <form action="{{ route('clients.import.confirm') }}" method="POST">
            @csrf
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700">
                Confirm import ({{ count($preview['create']) + count($preview['update']) }} rows)
            </button>
        </form>
        @else
        <p class="text-sm text-red-600 font-medium">Cannot confirm until all invalid rows are fixed.</p>
        @endif
        <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium">Back to clients</a>
        <a href="{{ route('clients.template') }}" class="text-sm text-indigo-600 font-medium">Download template</a>
    </div>
</div>
@endsection
