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
    }
}
