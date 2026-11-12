<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Support\DashboardLayout;
use App\Support\ModuleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLayoutSettingTest extends TestCase
{
    use RefreshDatabase;

    private function org(string $slug = 'layoutfirm'): Organization
    {
        return Organization::create([
            'name' => 'Layout Firm '.$slug,
            'slug' => $slug,
            'plan' => 'professional',
            'seat_limit' => 10,
        ]);
    }

    private function member(Organization $org, string $role): User
    {
        return User::factory()->create([
            'organization_id' => $org->id,
            'role' => $role,
            'module_access' => ModuleAccess::defaultsForRole($role),
            'mobile' => '9198765'.random_int(10000, 99999),
        ]);
    }

    public function test_new_users_follow_firm_default_which_starts_as_classic(): void
    {
        $user = $this->member($this->org(), 'associate');

        $this->assertNull(DashboardLayout::userChoice($user));
        $this->assertSame(DashboardLayout::CLASSIC, DashboardLayout::forUser($user));
    }

    public function test_layouts_not_built_yet_fall_back_to_classic(): void
    {
        $user = $this->member($this->org(), 'associate');
        $user->forceFill(['dashboard_layout' => DashboardLayout::BOARD])->save();

        $this->assertSame(DashboardLayout::BOARD, DashboardLayout::userChoice($user->fresh()));
        $this->assertSame(DashboardLayout::CLASSIC, DashboardLayout::forUser($user->fresh()));
    }

    public function test_user_can_switch_layout_from_dashboard(): void
    {
        $user = $this->member($this->org(), 'partner');

        $this->actingAs($user)
            ->post(route('dashboard.layout'), ['dashboard_layout' => DashboardLayout::CLASSIC])
            ->assertRedirect(route('dashboard'));
        $this->assertSame(DashboardLayout::CLASSIC, $user->fresh()->dashboard_layout);

        $this->actingAs($user)
            ->post(route('dashboard.layout'), ['dashboard_layout' => ''])
            ->assertRedirect(route('dashboard'));
        $this->assertNull($user->fresh()->dashboard_layout);
    }

    public function test_unavailable_or_unknown_layouts_are_rejected(): void
    {
        $user = $this->member($this->org(), 'partner');

        foreach ([DashboardLayout::BOARD, 'nonsense'] as $bad) {
            $this->actingAs($user)
                ->post(route('dashboard.layout'), ['dashboard_layout' => $bad])
                ->assertSessionHasErrors('dashboard_layout');
        }

        $this->assertNull($user->fresh()->dashboard_layout);
    }

    public function test_profile_settings_save_layout_and_firm_default(): void
    {
        $partner = $this->member($this->org(), 'partner');

        $this->actingAs($partner)
            ->put(route('settings.update'), [
                'name' => $partner->name,
                'email' => $partner->email,
                'mobile' => $partner->mobile,
                'theme' => 'modern',
                'dashboard_layout' => DashboardLayout::CLASSIC,
                DashboardLayout::FIRM_SETTING_KEY => DashboardLayout::CLASSIC,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(DashboardLayout::CLASSIC, $partner->fresh()->dashboard_layout);
        $this->assertSame(DashboardLayout::CLASSIC, Setting::get(DashboardLayout::FIRM_SETTING_KEY));
    }

    public function test_settings_page_shows_layout_picker(): void
    {
        $partner = $this->member($this->org(), 'partner');

        $this->actingAs($partner)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Dashboard layout')
            ->assertSee('Default dashboard layout')
            ->assertSee('Work board');
    }

    public function test_partner_sets_layout_for_a_team_member(): void
    {
        $org = $this->org();
        $partner = $this->member($org, 'partner');
        $associate = $this->member($org, 'associate');

        $this->actingAs($partner)
            ->patch(route('users.update-dashboard-layout', $associate), ['dashboard_layout' => DashboardLayout::CLASSIC])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(DashboardLayout::CLASSIC, $associate->fresh()->dashboard_layout);
    }

    public function test_users_page_lists_layout_per_member(): void
    {
        $org = $this->org();
        $partner = $this->member($org, 'partner');
        $this->member($org, 'associate');

        $this->actingAs($partner)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Dashboard layout')
            ->assertSee('Firm default (Classic)');
    }

    public function test_associate_cannot_set_layout_for_others(): void
    {
        $org = $this->org();
        $this->member($org, 'partner');
        $associate = $this->member($org, 'associate');
        $other = $this->member($org, 'associate');

        $this->actingAs($associate)
            ->patch(route('users.update-dashboard-layout', $other), ['dashboard_layout' => DashboardLayout::CLASSIC])
            ->assertForbidden();

        $this->assertNull($other->fresh()->dashboard_layout);
    }

    public function test_partner_cannot_change_users_in_another_firm(): void
    {
        $partner = $this->member($this->org('firm-a'), 'partner');
        $outsider = $this->member($this->org('firm-b'), 'associate');

        $response = $this->actingAs($partner)
            ->patch(route('users.update-dashboard-layout', $outsider), ['dashboard_layout' => DashboardLayout::CLASSIC]);

        $this->assertContains($response->status(), [403, 404]);
        $this->assertNull($outsider->fresh()->dashboard_layout);
    }

    public function test_dashboard_shows_layout_switcher(): void
    {
        $partner = $this->member($this->org(), 'partner');

        $this->actingAs($partner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-layout-switcher', false)
            ->assertSee('Action inbox');
    }
}
