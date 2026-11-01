<?php

namespace Tests;

use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // OrganizationContext is static and survives between tests in one PHP
        // process; a stale id from a previous test points at an organization
        // that doesn't exist in this test's fresh database.
        OrganizationContext::clear();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        OrganizationContext::clear();

        parent::tearDown();
    }
}
