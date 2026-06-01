<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ModuleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelFunctionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helper_methods(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $manager = User::factory()->create(['role' => 'manager']);
        $associate = User::factory()->create(['role' => 'associate']);
        $article = User::factory()->create(['role' => 'article']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->assertTrue($partner->isPartner());
        $this->assertTrue($manager->isManager());
        $this->assertTrue($associate->isAssociate());
        $this->assertTrue($article->isArticle());
        $this->assertFalse($staff->managesFirmModules());
        $this->assertTrue($manager->managesFirmModules());
    }

    public function test_can_access_module_merges_role_defaults_with_overrides(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['billing' => true],
        ]);

        $defaults = ModuleAccess::defaultsForRole('staff');
        $this->assertFalse($defaults['billing']);
        $this->assertTrue($staff->canAccessModule('billing'));
        $this->assertTrue(User::factory()->create(['role' => 'partner'])->canAccessModule('system'));
    }

    public function test_has_role_accepts_multiple_roles(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $this->assertTrue($user->hasRole('partner', 'manager'));
        $this->assertFalse($user->hasRole('partner'));
    }
}
