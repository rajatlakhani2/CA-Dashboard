@extends('layouts.app')

@section('header')
<div class="flex flex-wrap justify-between items-center w-full gap-2">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Unbilled Queue</h1>
        <p class="text-sm text-gray-500">Select items to generate an invoice.</p>
    </div>
    <div class="flex gap-2">
        <form action="{{ route('billing.apply-rules') }}" method="POST">@csrf
            <button type="submit" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-sm font-medium">Apply billing rules</button>
        </form>
        <a href="{{ route('billing-rules.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700">Manage rules</a>
    </div>
</div>
@endsection

@section('content')
<div class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
    <strong>Completed tasks</strong> (including unassigned) are billed from
    <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}" class="font-semibold underline">Invoices → Unbilled Work</a>,
    not this Billing Queue.
</div>
<div class="space-y-6">
    @forelse($clients as $client)
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <form action="{{ route('billing.process') }}" method="POST">
            @csrf

            <div class="px-4 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <h3 class="text-lg font-semibold leading-6 text-gray-900">{{ $client->name }}</h3>
                    <span class="text-xs text-gray-500">({{ $client->client_code }})</span>
                </div>
                <div class="flex gap-2">
                    <form action="{{ route('billing.create-draft') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <button type="submit" class="bg-white border border-indigo-300 text-indigo-700 hover:bg-indigo-50 px-3 py-1.5 rounded text-sm font-medium">
                            Create draft invoice
                        </button>
                    </form>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded text-sm font-medium">
                        Generate Invoice
                    </button>
                </div>
            </div>

            <ul role="list" class="divide-y divide-gray-200">
                @foreach($client->services as $clientService)
                @foreach($clientService->dues as $due)
                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                    <div class="flex items-center">
                        <input type="checkbox" name="dues[]" value="{{ $due->id }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mr-4">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-indigo-600">{{ $clientService->service->name }}</p>
                            <p class="text-xs text-gray-500">Due: {{ $due->due_date->format('d M Y') }} | Completed: {{ $due->completed_at->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                ₹ {{ number_format($due->billing_amount, 2) }}
                            </span>
                        </div>
                    </div>
                </li>
                @endforeach
                @endforeach

                @foreach($client->worksheets as $ws)
                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 bg-gray-50/50">
                    <div class="flex items-center">
                        <input type="checkbox" name="worksheets[]" value="{{ $ws->id }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mr-4">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-indigo-800">{{ $ws->description }} (Ad-hoc Worksheet)</p>
                            <p class="text-xs text-gray-500">Date: {{ $ws->date->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                ₹ {{ number_format($ws->amount, 2) }}
                            </span>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </form>
    </div>
    @empty
    @include('partials.empty-state', [
        'title' => 'No unbilled items',
        'description' => 'All completed work has been invoiced. New dues and worksheets will appear here.',
        'icon' => 'inbox',
    ])
    @endforelse
</div>
@endsection
