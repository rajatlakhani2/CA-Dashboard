<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Support\ModuleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTimezoneSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_save_timezone_in_profile_settings(): void
    {
        $org = Organization::create([
            'name' => 'TZ Firm',
            'slug' => 'tzfirm',
            'plan' => 'professional',
            'seat_limit' => 5,
        ]);

        $partner = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'partner',
            'module_access' => ModuleAccess::defaultsForRole('partner'),
            'mobile' => '919876543210',
        ]);

        $this->actingAs($partner)
            ->put(route('settings.update'), [
                'name' => $partner->name,
                'email' => $partner->email,
                'mobile' => $partner->mobile,
                'theme' => 'modern',
                'timezone' => 'Asia/Kolkata',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Asia/Kolkata', $partner->fresh()->timezone);
    }
}
