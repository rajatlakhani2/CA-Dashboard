<?php

namespace App\Http\Controllers;

use App\Models\BillingRule;
use App\Models\Client;
use App\Models\Service;
use App\Services\BillingRuleApplier;
use Illuminate\Http\Request;

class BillingRuleController extends Controller
{
    public function index()
    {
        $this->authorizePartnerManager();

        return view('billing.rules', [
            'rules' => BillingRule::with(['service', 'client'])->orderBy('name')->get(),
            'services' => Service::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->limit(500)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePartnerManager();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'client_id' => 'nullable|exists:clients,id',
            'rule_type' => 'required|in:fixed_fee,use_due_amount',
            'fixed_amount' => 'nullable|numeric|min:0',
            'use_due_amount' => 'boolean',
            'auto_draft_invoice' => 'boolean',
        ]);

        $data['use_due_amount'] = $request->boolean('use_due_amount', $data['rule_type'] === 'use_due_amount');
        $data['auto_draft_invoice'] = $request->boolean('auto_draft_invoice');
        $data['is_active'] = true;

        BillingRule::create($data);

        return back()->with('success', 'Billing rule created.');
    }

    public function destroy(BillingRule $billingRule)
    {
        $this->authorizePartnerManager();
        $billingRule->delete();

        return back()->with('success', 'Billing rule removed.');
    }

    public function apply(BillingRuleApplier $applier)
    {
        $this->authorizePartnerManager();
        $result = $applier->applyToUnbilledDues();

        return back()->with(
            'success',
            "Applied billing amounts to {$result['applied']} completed due(s)."
        );
    }

    private function authorizePartnerManager(): void
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);
    }
}
