<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Http\Request;

class SmartDocumentController extends Controller
{
    public function index()
    {
        // List all clients for the dropdown selection
        $clients = Client::orderBy('name')
            ->select('id', 'name', 'client_code')
            ->get();

        return view('smart-documents.index', compact('clients'));
    }

    public function show(Client $client)
    {
        $client->load('documents');
        return view('smart-documents.show', compact('client'));
    }
}
