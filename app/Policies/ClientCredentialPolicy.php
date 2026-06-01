<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\User;

class ClientCredentialPolicy
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

    public function view(User $user, ClientCredential $credential): bool
    {
        return $this->canAccessClient($user, $credential->client);
    }

    public function update(User $user, ClientCredential $credential): bool
    {
        return $this->canAccessClient($user, $credential->client);
    }

    public function delete(User $user, ClientCredential $credential): bool
    {
        return $this->canAccessClient($user, $credential->client);
    }

    private function canAccessClient(User $user, ?Client $client): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager() || ! $client) {
            return false;
        }

        if (! $user->branch_id || ! $client->branch_id) {
            return true;
        }

        return (int) $user->branch_id === (int) $client->branch_id;
    }
}
