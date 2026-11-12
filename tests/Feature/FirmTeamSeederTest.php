<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\FirmTeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmTeamSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_succeeds_when_duplicate_legacy_rajat_rows_exist(): void
    {
        $organization = Organization::create([
            'slug' => 'rla',
            'name' => 'RL Associates',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
        ]);

        User::factory()->create([
            'id' => 1,
            'name' => 'Old Rajat',
            'email' => 'rajat@rla.local',
            'role' => 'staff',
            'organization_id' => null,
        ]);

        User::factory()->create([
            'name' => 'Rajat Lakhani',
            'email' => 'rajat@rlassociates.in',
            'role' => 'partner',
            'organization_id' => $organization->id,
        ]);

        $this->seed(FirmTeamSeeder::class);

        $this->assertSame(
            1,
            User::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('email', 'rajat@rlassociates.in')
                ->count()
        );

        $partner = User::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('email', 'rajat@rlassociates.in')
            ->first();

        $this->assertSame('partner', $partner->role);
        $this->assertSame('Rajat Lakhani', $partner->name);
    }

    public function test_seeder_never_changes_existing_passwords_or_deletes_staff(): void
    {
        $organization = Organization::create([
            'slug' => 'rla', 'name' => 'RL Associates', 'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25, 'is_active' => true,
        ]);

        $partner = User::factory()->create([
            'email' => 'rajat@rlassociates.in', 'role' => 'partner',
            'organization_id' => $organization->id, 'password' => 'my-strong-secret-1',
        ]);
        $realArticle = User::factory()->create([
            'email' => 'priya@rlassociates.in', 'role' => 'article', 'organization_id' => $organization->id,
        ]);
        $realAssociate = User::factory()->create([
            'email' => 'karan@rlassociates.in', 'role' => 'associate', 'organization_id' => $organization->id,
        ]);

        $this->seed(FirmTeamSeeder::class);
        $this->seed(FirmTeamSeeder::class);

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('my-strong-secret-1', $partner->fresh()->password));
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('password', $partner->fresh()->password));
        $this->assertNotNull($realArticle->fresh());
        $this->assertNotNull($realAssociate->fresh());
    }

    public function test_new_seed_accounts_never_get_the_old_default_password(): void
    {
        $this->seed(FirmTeamSeeder::class);

        $users = User::withoutGlobalScopes()->whereIn('email', ['rajat@rlassociates.in', 'associate@rlassociates.in', 'article@rlassociates.in'])->get();
        $this->assertCount(3, $users);
        foreach ($users as $u) {
            $this->assertFalse(\Illuminate\Support\Facades\Hash::check('password', $u->password), $u->email);
        }
    }
}
