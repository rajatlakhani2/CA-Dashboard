<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ClientWorksheet;

class ClientWorksheetController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        $client->worksheets()->create([
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'created_by' => auth()->id(),
            'is_billed' => false,
        ]);

        return back()->with('success', 'Worksheet item added successfully.');
    }

    public function destroy(Client $client, ClientWorksheet $worksheet)
    {
        $this->authorize('update', $client);

        if ($worksheet->client_id !== $client->id) {
            abort(403);
        }

        if ($worksheet->is_billed) {
            return back()->with('error', 'Cannot delete a billed worksheet item.');
        }

        $worksheet->delete();

        return back()->with('success', 'Worksheet item deleted successfully.');
    }
}
