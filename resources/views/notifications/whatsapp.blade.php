@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>WhatsApp Notifications</span>
    <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Simulation Mode</span>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Info Card -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    This module simulates sending WhatsApp messages. In a production environment, you would integrate with a provider like Twilio, Wati, or Meta Cloud API.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Send Test Message -->
        <div class="bg-bg-card shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-text-main">Send Test Message</h3>
                <div class="mt-2 max-w-xl text-sm text-text-secondary">
                    <p>Select a client and a template to trigger a simulated message.</p>
                </div>
                <form action="{{ route('whatsapp.send-test') }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-text-main">Client</label>
                        <select id="client_id" name="client_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-line focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="template_key" class="block text-sm font-medium text-text-main">Template</label>
                        <select id="template_key" name="template_key" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-line focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            @foreach($templates as $key => $content)
                            <option value="{{ $key }}">{{ ucwords(str_replace('_', ' ', $key)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Templates Preview -->
        <div class="bg-bg-card shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-text-main">Active Templates</h3>
                <ul class="mt-4 space-y-4">
                    @foreach($templates as $key => $content)
                    <li class="bg-bg-body p-3 rounded-md border border-line">
                        <h4 class="text-sm font-bold text-indigo-600 uppercase mb-1">{{ str_replace('_', ' ', $key) }}</h4>
                        <p class="text-xs text-text-secondary italic">"{{ $content }}"</p>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection