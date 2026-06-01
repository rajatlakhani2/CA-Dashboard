<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    public function view(User $user): bool
    {
        return true;
    }

    public function updateProfile(User $user): bool
    {
        return true;
    }

    public function updateFirm(User $user): bool
    {
        return $user->isPartner();
    }

    public function manageUsers(User $user): bool
    {
        return $user->isPartner();
    }
}

