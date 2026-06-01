<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('partner', 'manager', 'associate');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function createForClient(User $user, Client $client): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        if (! $user->branch_id || ! $client->branch_id) {
            return true;
        }

        return (int) $user->branch_id === (int) $client->branch_id;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->canAccessInvoice($user, $invoice);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('partner', 'manager')
            && $this->canAccessInvoice($user, $invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('partner', 'manager')
            && $this->canAccessInvoice($user, $invoice);
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('partner', 'manager')
            && $this->canAccessInvoice($user, $invoice);
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return $this->canAccessInvoice($user, $invoice);
    }

    private function canAccessInvoice(User $user, Invoice $invoice): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        $invoice->loadMissing('client');

        if ($user->isAssociate()) {
            return $invoice->client
                && (int) $invoice->client->manager_id === (int) $user->id
                && $invoice->client->approval_status === Client::APPROVAL_APPROVED;
        }

        if (! $user->isManager()) {
            return false;
        }

        $invoiceBranchId = $invoice->branch_id ?: $invoice->client?->branch_id;

        if (! $user->branch_id || ! $invoiceBranchId) {
            return true;
        }

        return (int) $user->branch_id === (int) $invoiceBranchId;
    }
}
