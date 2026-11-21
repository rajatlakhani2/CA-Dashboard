<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\OrganizationContext;
use App\Support\SessionIdle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdleLogoutTest extends TestCase
{
    use RefreshDatabase;

    private function partner(): User
    {
        $org = \App\Models\Organization::create([
            'slug' => 'idle-'.uniqid(),
            'name' => 'Idle Test Firm',
            'plan' => \App\Models\Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
        ]);

        return User::factory()->create(['role' => 'partner', 'organization_id' => $org->id]);
    }

    public function test_default_limit_is_five_minutes(): void
    {
        $this->assertSame(5, SessionIdle::minutesFor($this->partner()));
    }

    public function test_firm_setting_is_used_and_clamped(): void
    {
        $user = $this->partner();
        OrganizationContext::set($user->organization_id);
        Setting::create(['key' => 'auto_logout_minutes', 'value' => '30', 'organization_id' => $user->organization_id]);
        OrganizationContext::clear();
        Setting::flushCache();

        $this->assertSame(30, SessionIdle::minutesFor($user));
    }

    public function test_user_idle_past_limit_is_signed_out_on_next_request(): void
    {
        $user = $this->partner();
        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(6)->timestamp])
            ->get('/home')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_ping_keeps_session_alive(): void
    {
        $user = $this->partner();
        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(2)->timestamp])
            ->post(route('session.ping'))
            ->assertNoContent();
        $this->assertAuthenticated();
    }

    public function test_background_refresh_does_not_count_as_activity(): void
    {
        $user = $this->partner();
        $start = now()->subMinutes(2)->timestamp;
        $this->actingAs($user)
            ->withSession(['last_activity_at' => $start])
            ->withHeader('X-Background-Refresh', '1')
            ->get('/home');
        $this->assertSame($start, session('last_activity_at'));
    }
}
