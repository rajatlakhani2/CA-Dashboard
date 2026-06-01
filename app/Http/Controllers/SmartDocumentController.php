<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Http\Request;

class SmartDocumentController extends Controller
{
    public function index()
    {
        $clients = Client::query()
            ->visibleTo(auth()->user())
            ->orderBy('name')
            ->select('id', 'name', 'client_code')
            ->get();

        return view('smart-documents.index', compact('clients'));
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);

        $client->load('documents');
        return view('smart-documents.show', compact('client'));
    }
}
