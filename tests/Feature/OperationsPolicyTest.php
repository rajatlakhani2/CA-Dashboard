<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OperationsPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_update_profile_but_not_firm_settings(): void
    {
        Setting::set('company_name', 'Original Firm');
        Setting::set('default_gst_rate', '18');

        $manager = User::factory()->create([
            'role' => 'manager',
            'name' => 'Manager Old',
            'email' => 'manager@example.com',
            'theme' => 'modern',
        ]);

        $this->actingAs($manager)
            ->put(route('settings.update'), [
                'name' => 'Manager New',
                'email' => 'manager@example.com',
                'theme' => 'dense',
                'mobile' => '919999999999',
                'company_name' => 'Changed Firm',
                'default_gst_rate' => '28',
            ])
            ->assertRedirect();

        $manager->refresh();

        $this->assertSame('Manager New', $manager->name);
        $this->assertSame('dense', $manager->theme);
        $this->assertSame('919999999999', $manager->mobile);
        $this->assertSame('Original Firm', Setting::get('company_name'));
        $this->assertSame('18', Setting::get('default_gst_rate'));
    }

    public function test_partner_can_update_firm_settings(): void
    {
        $partner = User::factory()->create([
            'role' => 'partner',
            'name' => 'Partner User',
            'email' => 'partner@example.com',
            'theme' => 'modern',
            'mobile' => '919888877766',
        ]);

        $this->actingAs($partner)
            ->put(route('settings.update'), [
                'name' => 'Partner User',
                'email' => 'partner@example.com',
                'mobile' => '919888877766',
                'theme' => 'modern',
                'company_name' => 'Partner Firm',
                'company_address' => 'Main Office',
                'company_email' => 'billing@example.com',
                'firm_gstin' => '27ABCDE1234F1Z5',
                'firm_state_code' => '27',
                'default_sac_code' => '998231',
                'default_gst_rate' => '18',
                'reminder_time_1' => '09:30',
                'reminder_time_2' => '18:30',
            ])
            ->assertRedirect();

        $this->assertSame('Partner Firm', Setting::get('company_name'));
        $this->assertSame('Main Office', Setting::get('company_address'));
        $this->assertSame('billing@example.com', Setting::get('company_email'));
        $this->assertSame('27ABCDE1234F1Z5', Setting::get('firm_gstin'));
        $this->assertSame('27', Setting::get('firm_state_code'));
        $this->assertSame('09:30', Setting::get('reminder_time_1'));
        $this->assertSame('18:30', Setting::get('reminder_time_2'));
    }

    public function test_firm_settings_are_hidden_from_manager_profile_page(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertDontSee('Company Details')
            ->assertDontSee('WhatsApp Reminder Settings');
    }

    public function test_branch_policy_is_partner_only(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $partner = User::factory()->create(['role' => 'partner']);
        $branch = Branch::create(['name' => 'Main Branch', 'code' => 'MAIN']);

        $this->assertFalse(Gate::forUser($manager)->allows('viewAny', Branch::class));
        $this->assertFalse(Gate::forUser($manager)->allows('delete', $branch));
        $this->assertTrue(Gate::forUser($partner)->allows('viewAny', Branch::class));
        $this->assertTrue(Gate::forUser($partner)->allows('delete', $branch));
    }

    public function test_unused_branch_resource_routes_are_not_usable_as_pages(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $this->actingAs($partner)->get('/branches/create')->assertStatus(405);
    }
}
