<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
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

    public function update(User $user, Subscription $subscription): bool
    {
        return $this->canAccessSubscription($user, $subscription);
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $this->canAccessSubscription($user, $subscription);
    }

    private function canAccessSubscription(User $user, Subscription $subscription): bool
    {
        $subscription->loadMissing('client');

        return $subscription->client
            ? $this->canAccessClient($user, $subscription->client)
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
