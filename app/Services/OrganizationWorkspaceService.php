<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class OrganizationWorkspaceService
{
    public function forUser(?User $user): array
    {
        if (! Schema::hasTable('organizations') || ! Schema::hasColumn('users', 'organization_id')) {
            return $this->legacyWorkspace($user);
        }

        if (! $user?->organization_id) {
            return $this->legacyWorkspace($user);
        }

        $organization = Organization::find($user->organization_id);
        if (! $organization) {
            return $this->legacyWorkspace($user);
        }

        $teamMembers = User::query()
            ->where('organization_id', $organization->id)
            ->orderByRaw("CASE WHEN role = 'partner' THEN 0 WHEN role = 'manager' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'branch_id']);

        $activeTasksByUser = Task::query()
            ->where('organization_id', $organization->id)
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, count(*) as open_count')
            ->groupBy('assigned_to')
            ->pluck('open_count', 'assigned_to');

        $team = $teamMembers->map(fn (User $member) => [
            'id' => $member->id,
            'name' => $member->name,
            'role' => ucfirst((string) $member->role),
            'initials' => $this->initials($member->name),
            'is_you' => $member->id === $user->id,
            'open_tasks' => (int) ($activeTasksByUser[$member->id] ?? 0),
        ]);

        return [
            'organization' => $organization,
            'name' => $organization->name,
            'plan' => $organization->planLabel(),
            'seat_limit' => $organization->seat_limit,
            'seat_used' => $teamMembers->count(),
            'seats_remaining' => $organization->seatsRemaining(),
            'team' => $team,
            'role_label' => ucfirst((string) $user->role),
        ];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = collect($parts)->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('');

        return $letters ?: '?';
    }

    private function legacyWorkspace(?User $user): array
    {
        $teamMembers = User::query()
            ->orderByRaw("CASE WHEN role = 'partner' THEN 0 WHEN role = 'manager' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'branch_id']);

        $activeTasksByUser = Task::query()
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, count(*) as open_count')
            ->groupBy('assigned_to')
            ->pluck('open_count', 'assigned_to');

        $team = $teamMembers->map(fn (User $member) => [
            'id' => $member->id,
            'name' => $member->name,
            'role' => ucfirst((string) $member->role),
            'initials' => $this->initials($member->name),
            'is_you' => $user && $member->id === $user->id,
            'open_tasks' => (int) ($activeTasksByUser[$member->id] ?? 0),
        ]);

        $seatUsed = $teamMembers->count();

        return [
            'organization' => null,
            'name' => Setting::get('company_name', 'My CA Firm'),
            'plan' => 'Professional',
            'seat_limit' => max(25, $seatUsed),
            'seat_used' => $seatUsed,
            'seats_remaining' => max(0, 25 - $seatUsed),
            'team' => $team,
            'role_label' => $user ? ucfirst((string) $user->role) : 'User',
        ];
    }
}
