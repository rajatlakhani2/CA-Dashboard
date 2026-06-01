<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function view(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function export(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }
}
