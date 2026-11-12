<?php

namespace Tests;

use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // The organisation context is static; without this it leaks from one test into the next.
        OrganizationContext::clear();

        parent::setUp();

        // Behave like a signed-in firm: records a test creates belong to the first
        // organisation it sets up, unless the test chooses a context itself.
        Organization::created(function (Organization $organization) {
            if (OrganizationContext::id() === null) {
                OrganizationContext::set($organization->id);
            }
        });
    }

    protected function tearDown(): void
    {
        OrganizationContext::clear();

        parent::tearDown();
    }
}
