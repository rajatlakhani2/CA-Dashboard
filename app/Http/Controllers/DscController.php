<?php

namespace App\Http\Controllers;

use App\Models\Dsc;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DscController extends Controller
{
    public function index(Request $request)
    {
        $query = Dsc::with('client');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Auto-mark expired
        Dsc::where('status', 'Active')
            ->where('expiry_date', '<', Carbon::today())
            ->update(['status' => 'Expired']);

        $dscs = $query->orderBy('expiry_date')->paginate(20);
        $expiringSoonCount = Dsc::where('status', 'Active')
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('expiry_date', '>=', Carbon::today())
            ->count();

        $clients = Client::orderBy('name')->get();

        return view('dscs.index', compact('dscs', 'expiringSoonCount', 'clients'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        return view('dscs.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'holder_name' => 'required|string|max:255',
            'class_type' => 'required|in:Class 2,Class 3',
            'provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'notes' => 'nullable|string',
        ]);

        Dsc::create($request->all());
        return redirect()->route('dscs.index')->with('success', 'DSC added successfully.');
    }

    public function edit(Dsc $dsc)
    {
        $clients = Client::orderBy('name')->get();
        return view('dscs.edit', compact('dsc', 'clients'));
    }

    public function update(Request $request, Dsc $dsc)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'holder_name' => 'required|string|max:255',
            'class_type' => 'required|in:Class 2,Class 3',
            'expiry_date' => 'required|date',
        ]);

        $dsc->update($request->all());
        return redirect()->route('dscs.index')->with('success', 'DSC updated.');
    }

    public function destroy(Dsc $dsc)
    {
        $dsc->delete();
        return redirect()->route('dscs.index')->with('success', 'DSC deleted.');
    }
}
