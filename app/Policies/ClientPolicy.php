<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isArticle();
    }

    public function view(User $user, Client $client): bool
    {
        return $this->canAccessClient($user, $client);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('partner', 'manager', 'associate', 'article');
    }

    public function approve(User $user, Client $client): bool
    {
        return $user->isPartner() && $client->isPendingApproval();
    }

    public function update(User $user, Client $client): bool
    {
        if ($client->isPendingApproval() && ! $user->isPartner()) {
            return false;
        }

        return $this->canAccessClient($user, $client);
    }

    public function export(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function import(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->canManageClient($user, $client);
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    private function canAccessClient(User $user, Client $client): bool
    {
        if ($user->isArticle()) {
            return false;
        }

        if ($user->isPartner()) {
            return true;
        }

        if (! $client->isPendingApproval() && $user->isAssociate()) {
            return (int) $client->manager_id === (int) $user->id;
        }

        if ($client->isPendingApproval()) {
            return false;
        }

        if ($user->isManager()) {
            if (! $user->branch_id || ! $client->branch_id) {
                return true;
            }

            return (int) $user->branch_id === (int) $client->branch_id;
        }

        if ((int) $client->manager_id === (int) $user->id) {
            return true;
        }

        return $client->tasks()
            ->where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            })
            ->exists();
    }

    private function canManageClient(User $user, Client $client): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager() || $client->isPendingApproval()) {
            return false;
        }

        if (! $user->branch_id || ! $client->branch_id) {
            return true;
        }

        return (int) $user->branch_id === (int) $client->branch_id;
    }
}
