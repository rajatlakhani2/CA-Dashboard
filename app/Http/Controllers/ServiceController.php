<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::all();
        return view('settings.services', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:services,code|max:50',
            'description' => 'nullable|string',
            'frequency' => 'required|in:Monthly,Quarterly,Half-Yearly,Annually,One-Time',
            'due_day' => 'nullable|integer|min:1|max:31',
            'due_month' => 'nullable|integer|min:1|max:12',
            'is_statutory' => 'boolean',
        ]);

        $validated['is_statutory'] = $request->has('is_statutory');

        Service::create($validated);

        return redirect()->back()->with('success', 'Service created successfully.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|in:Monthly,Quarterly,Half-Yearly,Annually,One-Time',
            'due_day' => 'nullable|integer|min:1|max:31',
            'due_month' => 'nullable|integer|min:1|max:12',
            'is_statutory' => 'boolean',
        ]);

        $validated['is_statutory'] = $request->has('is_statutory');

        $service->update($validated);

        return redirect()->back()->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}
