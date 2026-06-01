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
        $this->authorize('viewAny', Subscription::class);

        $query = Subscription::with(['client', 'service']);
        $this->scopeSubscriptionsToUser($query);

        $subscriptions = $query->latest()->paginate(20);
        return view('subscriptions.index', compact('subscriptions'));
    }

    public function create()
    {
        $this->authorize('create', Subscription::class);

        $clients = $this->clientOptionsQuery()->orderBy('name')->get();
        $services = Service::all();
        return view('subscriptions.create', compact('clients', 'services'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Subscription::class);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'nullable|exists:services,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:' . implode(',', [
                Subscription::FREQUENCY_MONTHLY,
                Subscription::FREQUENCY_QUARTERLY,
                Subscription::FREQUENCY_SEMI_ANNUALLY,
                Subscription::FREQUENCY_ANNUALLY,
            ]),
            'billing_day' => 'required|integer|min:1|max:31',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $this->authorize('createForClient', [Subscription::class, Client::findOrFail($validated['client_id'])]);

        $subscription = new Subscription($validated);
        $subscription->next_billing_date = $subscription->calculateNextBillingDate();
        $subscription->save();

        return redirect()->route('subscriptions.index')->with('success', 'Subscription created successfully.');
    }

    public function toggle(Subscription $subscription)
    {
        $this->authorize('update', $subscription);

        $subscription->status = $subscription->status === Subscription::STATUS_ACTIVE
            ? Subscription::STATUS_PAUSED
            : Subscription::STATUS_ACTIVE;
        $subscription->save();

        return back()->with('success', 'Subscription status updated.');
    }

    public function destroy(Subscription $subscription)
    {
        $this->authorize('delete', $subscription);

        $subscription->delete();
        return redirect()->route('subscriptions.index')->with('success', 'Subscription cancelled.');
    }

    private function scopeSubscriptionsToUser($query): void
    {
        $user = auth()->user();

        if (! $user?->isManager() || ! $user->branch_id) {
            return;
        }

        $query->whereHas('client', function ($q) use ($user) {
            $q->whereNull('branch_id')
                ->orWhere('branch_id', $user->branch_id);
        });
    }

    private function clientOptionsQuery()
    {
        $query = Client::query();
        $user = auth()->user();

        if ($user?->isManager() && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        return $query;
    }
}
