<?php

namespace App\Services;

use App\Models\ClientCredential;
use App\Models\User;
use App\Support\GovernmentPortals;
use Illuminate\Database\Eloquent\Builder;

class GovernmentPortalLauncher
{
    /**
     * @return array<string, mixed>
     */
    public function buildLaunchPayload(string $portalId, ClientCredential $credential): array
    {
        $portal = GovernmentPortals::find($portalId);

        if (! GovernmentPortals::matchesCredential($portal, $credential)) {
            abort(404, 'Credential does not match this portal.');
        }

        $credential->loadMissing('client');

        $username = trim((string) $credential->username);
        $password = $credential->display_password;
        $tan = trim((string) ($credential->client?->tan ?? ''));

        $replacements = [
            '{username}' => $username,
            '{password}' => $password,
            '{tan}' => $tan,
        ];

        $fields = [];
        foreach ($portal['fields'] ?? [] as $name => $template) {
            $value = strtr((string) $template, $replacements);
            if ($value !== '') {
                $fields[$name] = $value;
            }
        }

        return [
            'portal' => $portal,
            'credential' => $credential,
            'client_name' => $credential->client?->name ?? 'Client',
            'username' => $username,
            'password' => $password,
            'fields' => $fields,
            'form_action' => $portal['form_action'] ?? $portal['login_url'],
            'form_method' => strtolower($portal['form_method'] ?? 'post'),
            'form_enctype' => $portal['form_enctype'] ?? 'application/x-www-form-urlencoded',
            'launch_mode' => $portal['launch_mode'],
            'login_url' => $portal['login_url'],
            'autofill_hint' => $portal['autofill_hint'] ?? '',
        ];
    }

    public function credentialsQuery(string $portalId, User $user): Builder
    {
        $portal = GovernmentPortals::find($portalId);

        $query = ClientCredential::query()
            ->with(['client:id,name,client_code,group_name,branch_id,tan'])
            ->whereHas('client', function (Builder $clientQuery) {
                $clientQuery->whereNull('deleted_at');
            })
            ->where(function (Builder $builder) use ($portal) {
                $builder->whereIn('category', $portal['categories']);

                foreach ($portal['keywords'] as $keyword) {
                    $like = '%'.strtolower($keyword).'%';
                    $builder->orWhereRaw('LOWER(portal_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(notes, "")) LIKE ?', [$like]);
                }
            })
            ->orderBy('portal_name');

        $this->scopeToUser($query, $user);

        return $query;
    }

    private function scopeToUser(Builder $query, User $user): void
    {
        if (! $user->isManager() || ! $user->branch_id) {
            return;
        }

        $query->whereHas('client', function (Builder $clientQuery) use ($user) {
            $clientQuery->where(function (Builder $branchScope) use ($user) {
                $branchScope->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        });
    }
}
