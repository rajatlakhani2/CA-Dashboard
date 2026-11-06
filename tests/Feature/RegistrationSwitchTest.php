<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationSwitchTest extends TestCase
{
    use RefreshDatabase;

    private array $signup = [
        'firm_name' => 'Stranger Firm',
        'workspace' => 'stranger',
        'admin_name' => 'Stranger',
        'admin_email' => 'stranger@example.test',
        'admin_password' => 'password123',
        'admin_password_confirmation' => 'password123',
    ];

    public function test_registration_is_closed_by_default(): void
    {
        config(['app.public_registration' => false]);

        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->signup)->assertNotFound();

        $this->assertDatabaseMissing('organizations', ['slug' => 'stranger']);
        $this->assertGuest();
    }

    public function test_login_page_hides_signup_link_when_closed(): void
    {
        config(['app.public_registration' => false]);

        $this->get(route('login'))->assertOk()->assertDontSee('Create a workspace');
    }

    public function test_registration_works_when_switched_on(): void
    {
        config(['app.public_registration' => true]);

        $this->get(route('login'))->assertSee('Create a workspace');
        $this->get('/register')->assertOk();
        $this->post('/register', $this->signup)->assertRedirect(route('dashboard'));

        $this->assertTrue(Organization::where('slug', 'stranger')->exists());
    }

    public function test_new_signup_partner_is_not_a_platform_admin(): void
    {
        config(['app.public_registration' => true, 'app.platform_admin_workspace' => 'rla']);

        $this->post('/register', $this->signup);

        $this->assertFalse(auth()->user()->isPlatformAdmin());
        $this->get('/system')->assertForbidden();
    }
}
