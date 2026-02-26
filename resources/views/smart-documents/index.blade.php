@extends('layouts.app')

@section('header')
Smart Archive (Document Vault)
@endsection

@section('content')
<div class="max-w-3xl mx-auto mt-10">
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="p-10 text-center">
            <div class="mx-auto h-20 w-20 bg-indigo-50 rounded-full flex items-center justify-center mb-6">
                <svg class="h-10 w-10 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
            </div>

            <h2 class="text-3xl font-bold text-gray-900 mb-2">Access Client Archive</h2>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">Select a client to view, manage, and upload their specific documents.</p>

            <div class="relative max-w-md mx-auto" x-data="{ 
                search: '', 
                open: false, 
                clients: {{ $clients->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->client_code])->toJson() }},
                get filteredClients() {
                    if (this.search === '') return [];
                    return this.clients.filter(client => 
                        client.name.toLowerCase().includes(this.search.toLowerCase()) || 
                        (client.code && client.code.toLowerCase().includes(this.search.toLowerCase()))
                    );
                },
                selectClient(id) {
                    window.location.href = '/smart-documents/' + id;
                }
            }">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        x-model="search"
                        @focus="open = true"
                        @click.away="open = false"
                        class="block w-full pl-10 pr-4 py-4 border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-lg"
                        placeholder="Search by client name or code...">
                </div>

                <!-- Dropdown Results -->
                <div x-show="open && filteredClients.length > 0"
                    class="absolute z-10 mt-2 w-full bg-white shadow-2xl rounded-xl max-h-60 overflow-y-auto border border-gray-100 ring-1 ring-black ring-opacity-5"
                    style="display: none;">
                    <ul class="py-1 text-left">
                        <template x-for="client in filteredClients" :key="client.id">
                            <li @click="selectClient(client.id)" class="cursor-pointer hover:bg-indigo-50 px-4 py-3 flex justify-between items-center transition-colors">
                                <div>
                                    <p class="font-medium text-gray-900" x-text="client.name"></p>
                                    <p class="text-xs text-gray-500" x-text="client.code"></p>
                                </div>
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </li>
                        </template>
                    </ul>
                </div>

                <div x-show="open && search !== '' && filteredClients.length === 0" class="absolute z-10 mt-2 w-full bg-white shadow-lg rounded-xl p-4 text-gray-500" style="display: none;">
                    No clients found.
                </div>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-4 text-left max-w-lg mx-auto">
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <h4 class="font-bold text-blue-800">Quick Access</h4>
                    <p class="text-sm text-blue-600 mt-1">Recently accessed folders appear here.</p>
                </div>
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                    <h4 class="font-bold text-emerald-800">Secure Storage</h4>
                    <p class="text-sm text-emerald-600 mt-1">All documents are encrypted and safe.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection