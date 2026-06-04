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
}
