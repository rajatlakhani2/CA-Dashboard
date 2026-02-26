@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <h1 class="text-2xl font-bold text-gray-900">Unbilled Queue</h1>
    <p class="text-sm text-gray-500">Select items to generate an invoice.</p>
</div>
@endsection

@section('content')
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
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded text-sm font-medium">
                    Generate Invoice
                </button>
            </div>

            <ul role="list" class="divide-y divide-gray-200">
                @foreach($client->optedServices as $service)
                @foreach($service->dues as $due)
                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                    <div class="flex items-center">
                        <input type="checkbox" name="dues[]" value="{{ $due->id }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mr-4">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-indigo-600">{{ $service->service->name }}</p>
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
            </ul>
        </form>
    </div>
    @empty
    <div class="text-center py-10">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No unbilled items</h3>
        <p class="mt-1 text-sm text-gray-500">Great job! All completed work has been invoiced.</p>
    </div>
    @endforelse
</div>
@endsection