<?php

namespace Tests\Unit\Support;

use App\Support\ModuleAccess;
use PHPUnit\Framework\TestCase;

class ModuleAccessTest extends TestCase
{
    public function test_partner_has_all_modules(): void
    {
        $defaults = ModuleAccess::defaultsForRole('partner');
        foreach (array_keys(ModuleAccess::MODULES) as $key) {
            $this->assertTrue($defaults[$key] ?? false, "Partner should access {$key}");
        }
    }

    public function test_associate_blocks_finance_modules(): void
    {
        $defaults = ModuleAccess::defaultsForRole('associate');
        $this->assertFalse($defaults['billing']);
        $this->assertFalse($defaults['reports']);
        $this->assertTrue($defaults['clients']);
        $this->assertTrue($defaults['tasks']);
    }

    public function test_article_clerk_minimal_access(): void
    {
        $defaults = ModuleAccess::defaultsForRole('article');
        $this->assertFalse($defaults['dashboard']);
        $this->assertTrue($defaults['clients']);
        $this->assertTrue($defaults['tasks']);
        $this->assertFalse($defaults['invoices']);
    }
}
