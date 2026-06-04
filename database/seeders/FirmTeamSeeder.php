<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FirmTeamSeeder extends Seeder
{
    public function run(): void
    {
        $this->upgradeLegacyRajatAccount();

        $organization = $this->ensureDefaultOrganization();

        $accounts = [
            [
                'name' => 'Rajat Lakhani',
                'email' => 'rajat@rlassociates.in',
                'role' => 'partner',
                'mobile' => '919999000001',
            ],
            [
                'name' => 'Nilesh Bhai',
                'email' => 'nilesh@rlassociates.in',
                'role' => 'associate',
                'mobile' => '919999000002',
            ],
            [
                'name' => 'Article Clerk',
                'email' => 'article@rlassociates.in',
                'role' => 'article',
                'mobile' => '919999000003',
            ],
        ];

        foreach ($accounts as $data) {
            $email = strtolower($data['email']);

            $attributes = [
                'name' => $data['name'],
                'role' => $data['role'],
                'mobile' => $data['mobile'],
                'organization_id' => $organization->id,
            ];

            if (Schema::hasColumn('users', 'module_access')) {
                $attributes['module_access'] = \App\Support\ModuleAccess::defaultsForRole($data['role']);
            }

            $user = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => $email, 'organization_id' => $organization->id],
                $attributes
            );

            // Always reset password (production recoveries often leave a bad hash).
            $user->forceFill(['password' => 'password'])->save();
        }
    }

    /**
     * Fix an existing Rajat row that was created as staff before firm roles were seeded.
     */
    private function upgradeLegacyRajatAccount(): void
    {
        $legacy = User::query()
            ->where(function ($query) {
                $query->where('email', 'rajat@rlassociates.in')
                    ->orWhere('email', 'rajat@rla.local')
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%rajat%']);
            })
            ->orderBy('id')
            ->first();

        if (! $legacy) {
            return;
        }

        $legacy->forceFill([
            'name' => 'Rajat Lakhani',
            'email' => 'rajat@rlassociates.in',
            'role' => 'partner',
        ])->save();
    }

    private function ensureDefaultOrganization(): Organization
    {
        $name = Setting::get('company_name', 'My CA Firm');

        return Organization::firstOrCreate(
            ['slug' => Str::slug($name) ?: 'default-workspace'],
            [
                'name' => $name,
                'plan' => Organization::PLAN_PROFESSIONAL,
                'seat_limit' => 25,
                'is_active' => true,
            ]
        );
    }
}
