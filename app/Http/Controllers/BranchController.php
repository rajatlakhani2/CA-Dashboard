<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Branch::class);

        $branches = Branch::all();
        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Branch::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches',
            'gstin' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorize('delete', $branch);

        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }
}
