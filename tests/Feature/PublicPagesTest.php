<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_public_pages_load_for_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('CA Dashboard')->assertSee('How work moves through the firm')->assertDontSee('Pricing');
        $this->get(route('about'))->assertOk()->assertSee('About CA Dashboard');
        $this->get(route('privacy'))->assertOk()->assertSee('Privacy policy');
        $this->get(route('terms'))->assertOk()->assertSee('Surat, Gujarat');
        $this->get(route('account.delete-info'))->assertOk()->assertSee('Delete your account');
        $this->get(route('login'))->assertOk()->assertSee('Workspace ID')->assertDontSee('Role-based module access');
        $this->get(route('register.organization'))->assertOk()->assertSee('Create your firm');
    }

    public function test_landing_page_has_no_private_only_wording(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsStringIgnoringCase('private workspace', $html);
        $this->assertStringNotContainsStringIgnoringCase('invitation', $html);
        $this->assertStringContainsString('Explore CA Dashboard', $html);
        $this->assertStringContainsString('Scroll to explore', $html);
    }

    public function test_guest_opening_dashboard_link_sees_front_page_but_home_goes_to_sign_in(): void
    {
        $this->get('/dashboard')->assertRedirect(route('landing'));
        $this->get('/home')->assertRedirect(route('login'));
    }
}
