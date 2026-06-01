<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\TdsEntry;
use App\Models\User;

class TdsEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function createForInvoice(User $user, Invoice $invoice): bool
    {
        return $this->canAccessInvoice($user, $invoice);
    }

    public function update(User $user, TdsEntry $tdsEntry): bool
    {
        return $this->canAccessTdsEntry($user, $tdsEntry);
    }

    public function delete(User $user, TdsEntry $tdsEntry): bool
    {
        return $this->canAccessTdsEntry($user, $tdsEntry);
    }

    private function canAccessTdsEntry(User $user, TdsEntry $tdsEntry): bool
    {
        $tdsEntry->loadMissing('invoice.client');

        return $tdsEntry->invoice
            ? $this->canAccessInvoice($user, $tdsEntry->invoice)
            : $user->isPartner();
    }

    private function canAccessInvoice(User $user, Invoice $invoice): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        $invoice->loadMissing('client');
        $invoiceBranchId = $invoice->branch_id ?: $invoice->client?->branch_id;

        if (! $user->branch_id || ! $invoiceBranchId) {
            return true;
        }

        return (int) $user->branch_id === (int) $invoiceBranchId;
    }
}
