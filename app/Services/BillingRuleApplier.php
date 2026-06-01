<?php

namespace App\Services;

use App\Models\BillingRule;
use App\Models\Invoice;
use App\Models\ServiceDue;
use Illuminate\Support\Collection;

class BillingRuleApplier
{
    public function applyToUnbilledDues(): array
    {
        $rules = BillingRule::query()->where('is_active', true)->with('service', 'client')->get();
        $applied = 0;
        $drafts = 0;

        $dues = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_COMPLETED)
            ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
            ->whereNull('invoice_id')
            ->with('clientService.service', 'clientService.client')
            ->get();

        foreach ($dues as $due) {
            $rule = $this->matchRule($rules, $due);
            if (! $rule) {
                continue;
            }

            $amount = $this->resolveAmount($rule, $due);
            if ($amount > 0) {
                $due->billing_amount = $amount;
                $due->save();
                $applied++;
            }

            if ($rule->auto_draft_invoice && $amount > 0) {
                $drafts++;
            }
        }

        return ['applied' => $applied, 'draft_candidates' => $drafts];
    }

    public function matchRule(Collection $rules, ServiceDue $due): ?BillingRule
    {
        $serviceId = $due->clientService->service_id;
        $clientId = $due->clientService->client_id;

        $clientSpecific = $rules->first(fn (BillingRule $r) => $r->client_id === $clientId
            && ($r->service_id === null || $r->service_id === $serviceId));

        if ($clientSpecific) {
            return $clientSpecific;
        }

        return $rules->first(fn (BillingRule $r) => $r->client_id === null && $r->service_id === $serviceId);
    }

    public function resolveAmount(BillingRule $rule, ServiceDue $due): float
    {
        if ($rule->rule_type === BillingRule::TYPE_FIXED_FEE && ! $rule->use_due_amount) {
            return (float) ($rule->fixed_amount ?? 0);
        }

        $existing = (float) ($due->billing_amount ?? 0);

        return $existing > 0 ? $existing : (float) ($rule->fixed_amount ?? 0);
    }
}
