<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['client', 'service'])->latest()->paginate(20);
        return view('subscriptions.index', compact('subscriptions'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $services = Service::all();
        return view('subscriptions.create', compact('clients', 'services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'nullable|exists:services,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:monthly,quarterly,semi-annually,annually',
            'billing_day' => 'required|integer|min:1|max:31',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $subscription = new Subscription($validated);
        $subscription->next_billing_date = $subscription->calculateNextBillingDate();
        $subscription->save();

        return redirect()->route('subscriptions.index')->with('success', 'Subscription created successfully.');
    }

    public function toggle(Subscription $subscription)
    {
        $subscription->status = $subscription->status === 'active' ? 'paused' : 'active';
        $subscription->save();

        return back()->with('success', 'Subscription status updated.');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('subscriptions.index')->with('success', 'Subscription cancelled.');
    }
}
