<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;

class WorkspaceOnboardingService
{
    public function forUser(?User $user): array
    {
        if (! $user?->isPartner()) {
            return ['show' => false, 'percent' => 100, 'steps' => []];
        }

        $steps = [
            [
                'key' => 'firm',
                'label' => 'Firm profile',
                'done' => (bool) \App\Models\Setting::get('company_name'),
                'url' => route('settings.index'),
            ],
            [
                'key' => 'clients',
                'label' => 'Add clients',
                'done' => Client::count() > 0,
                'url' => route('clients.create'),
            ],
            [
                'key' => 'team',
                'label' => 'Invite team',
                'done' => User::count() > 1,
                'url' => route('staff.index'),
            ],
            [
                'key' => 'task',
                'label' => 'First task',
                'done' => Task::count() > 0,
                'url' => route('tasks.create'),
            ],
            [
                'key' => 'invoice',
                'label' => 'First invoice',
                'done' => Invoice::count() > 0,
                'url' => route('invoices.create'),
            ],
        ];

        $done = collect($steps)->where('done', true)->count();
        $percent = (int) round(($done / count($steps)) * 100);

        return [
            'show' => $percent < 100 && ! session('onboarding_dismissed'),
            'percent' => $percent,
            'steps' => $steps,
        ];
    }
}
