<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Dsc;
use App\Models\User;

class DscPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function createForClient(User $user, Client $client): bool
    {
        return $this->canAccessClient($user, $client);
    }

    public function update(User $user, Dsc $dsc): bool
    {
        return $this->canAccessDsc($user, $dsc);
    }

    public function delete(User $user, Dsc $dsc): bool
    {
        return $this->canAccessDsc($user, $dsc);
    }

    private function canAccessDsc(User $user, Dsc $dsc): bool
    {
        $dsc->loadMissing('client');

        return $dsc->client
            ? $this->canAccessClient($user, $dsc->client)
            : $user->isPartner();
    }

    private function canAccessClient(User $user, Client $client): bool
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
}
