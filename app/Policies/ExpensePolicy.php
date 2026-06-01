<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->canAccessExpense($user, $expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->canAccessExpense($user, $expense);
    }

    private function canAccessExpense(User $user, Expense $expense): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        $expense->loadMissing('user');
        $expenseBranchId = $expense->user?->branch_id;

        if (! $user->branch_id || ! $expenseBranchId) {
            return true;
        }

        return (int) $user->branch_id === (int) $expenseBranchId;
    }
}
