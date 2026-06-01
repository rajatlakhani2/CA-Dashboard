<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->canAccessPayment($user, $payment);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->canAccessPayment($user, $payment);
    }

    public function download(User $user, Payment $payment): bool
    {
        return $this->canAccessPayment($user, $payment);
    }

    private function canAccessPayment(User $user, Payment $payment): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        $payment->loadMissing('invoice.client');
        $invoice = $payment->invoice;
        $invoiceBranchId = $invoice?->branch_id ?: $invoice?->client?->branch_id;

        if (! $user->branch_id || ! $invoiceBranchId) {
            return true;
        }

        return (int) $user->branch_id === (int) $invoiceBranchId;
    }
}

