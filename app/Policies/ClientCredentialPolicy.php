<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\User;

class ClientCredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('partner', 'manager') && $user->canAccessModule('credentials');
    }

    public function create(User $user): bool
    {
        return $user->canAccessModule('credentials')
            && $user->hasRole('partner', 'manager', 'associate');
    }

    public function createForClient(User $user, Client $client): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        return app(ClientPolicy::class)->update($user, $client);
    }

    public function view(User $user, ClientCredential $credential): bool
    {
        return $this->canAccessCredential($user, $credential);
    }

    public function update(User $user, ClientCredential $credential): bool
    {
        return $this->canAccessCredential($user, $credential);
    }

    public function delete(User $user, ClientCredential $credential): bool
    {
        return $this->canAccessCredential($user, $credential);
    }

    private function canAccessCredential(User $user, ClientCredential $credential): bool
    {
        if (! $user->canAccessModule('credentials')) {
            return false;
        }

        if ($user->hasRole('partner', 'manager')) {
            return app(ClientPolicy::class)->view($user, $credential->client);
        }

        return app(ClientPolicy::class)->update($user, $credential->client);
    }
}
