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
        $organization = $this->ensureDefaultOrganization();

        $this->upgradeLegacyRajatAccount($organization->id);

        $accounts = [
            [
                'name' => 'Rajat Lakhani',
                'email' => 'rajat@rlassociates.in',
                'role' => 'partner',
                'mobile' => '919999000001',
            ],
            [
                'name' => 'Firm Associate',
                'email' => 'associate@rlassociates.in',
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

            // Required on INSERT (MySQL has no default for password).
            $attributes['password'] = 'password';

            $user = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => $email, 'organization_id' => $organization->id],
                $attributes
            );

            // Always reset password (production recoveries often leave a bad hash).
            $user->forceFill(['password' => 'password'])->save();
        }

        $this->removeLegacyNamedSeedUsers($organization->id);
    }

    /** Drop old firm-specific demo users (SaaS uses generic roles only). */
    private function removeLegacyNamedSeedUsers(int $organizationId): void
    {
        User::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where(function ($query) {
                $query->whereIn('email', ['nilesh@rlassociates.in', 'nilesh@rla.local'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%nilesh%']);
            })
            ->delete();
    }

    /**
     * Fix an existing Rajat row that was created as staff before firm roles were seeded.
     * Production may already have a correct org-scoped partner row — never reassign a second copy.
     */
    private function upgradeLegacyRajatAccount(int $organizationId): void
    {
        $canonicalEmail = 'rajat@rlassociates.in';
        $legacyEmails = ['rajat@rlassociates.in', 'rajat@rla.local'];

        $canonical = User::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereRaw('LOWER(email) = ?', [$canonicalEmail])
            ->first();

        if ($canonical) {
            User::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->whereIn('email', $legacyEmails)
                ->where('id', '!=', $canonical->id)
                ->delete();

            User::withoutGlobalScopes()
                ->whereIn('email', $legacyEmails)
                ->whereNull('organization_id')
                ->where('id', '!=', $canonical->id)
                ->delete();

            $canonical->forceFill([
                'name' => 'Rajat Lakhani',
                'role' => 'partner',
            ])->save();

            return;
        }

        $legacy = User::withoutGlobalScopes()
            ->whereIn('email', $legacyEmails)
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id')
                    ->orWhere('organization_id', $organizationId);
            })
            ->orderBy('id')
            ->first();

        if (! $legacy) {
            return;
        }

        $duplicateInOrg = User::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereRaw('LOWER(email) = ?', [$canonicalEmail])
            ->where('id', '!=', $legacy->id)
            ->exists();

        if ($duplicateInOrg) {
            if ((int) $legacy->organization_id === $organizationId) {
                $legacy->delete();
            }

            return;
        }

        $legacy->forceFill([
            'name' => 'Rajat Lakhani',
            'email' => $canonicalEmail,
            'role' => 'partner',
            'organization_id' => $organizationId,
        ])->save();
    }

    private function ensureDefaultOrganization(): Organization
    {
        $name = Setting::get('company_name', 'My CA Firm');

        $slug = 'rla';
        if (Organization::where('slug', $slug)->where('name', '!=', $name)->exists()) {
            $slug = Str::slug($name) ?: 'default-workspace';
        }

        return Organization::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'plan' => Organization::PLAN_PROFESSIONAL,
                'seat_limit' => 25,
                'is_active' => true,
            ]
        );
    }
}
