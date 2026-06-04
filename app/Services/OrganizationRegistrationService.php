<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Support\ModuleAccess;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationRegistrationService
{
    /**
     * @param  array{firm_name: string, workspace: string, admin_name: string, admin_email: string, admin_password: string}  $data
     */
    public function register(array $data): Organization
    {
        $slug = $this->normalizeSlug($data['workspace']);

        return DB::transaction(function () use ($data, $slug) {
            $organization = Organization::create([
                'name' => $data['firm_name'],
                'slug' => $slug,
                'plan' => Organization::PLAN_PROFESSIONAL,
                'seat_limit' => 25,
                'is_active' => true,
            ]);

            OrganizationContext::set($organization->id);

            Setting::set('company_name', $data['firm_name']);

            User::create([
                'organization_id' => $organization->id,
                'name' => $data['admin_name'],
                'email' => strtolower($data['admin_email']),
                'password' => $data['admin_password'],
                'role' => 'partner',
                'module_access' => ModuleAccess::defaultsForRole('partner'),
            ]);

            OrganizationContext::clear();

            return $organization;
        });
    }

    public function normalizeSlug(string $workspace): string
    {
        $slug = Str::slug(Str::lower(trim($workspace)), '-');

        if ($slug === '' || strlen($slug) < 3) {
            throw new \InvalidArgumentException('Workspace ID must be at least 3 characters (letters, numbers, hyphens).');
        }

        return $slug;
    }

    public function slugAvailable(string $slug): bool
    {
        return ! Organization::where('slug', $this->normalizeSlug($slug))->exists();
    }
}
