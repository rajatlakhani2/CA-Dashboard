<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalErrorDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_error_demo_redirects_with_payload(): void
    {
        $org = Organization::create([
            'name' => 'Demo Firm',
            'slug' => 'demo-firm',
            'plan' => 'starter',
            'seat_limit' => 5,
        ]);

        $partner = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'partner',
        ]);

        $response = $this->actingAs($partner)
            ->get(route('demo.portal-error'));

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('portal_error');
        $payload = session('portal_error');
        $this->assertIsArray($payload);
        $this->assertStringContainsString('Required field missing', $payload['title'] ?? '');
    }
}
