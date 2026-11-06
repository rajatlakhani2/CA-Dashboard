<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallableAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_valid_and_its_icons_exist(): void
    {
        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('VouchEx', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertStringStartsWith('/dashboard', $manifest['start_url']);

        $sizes = [];
        foreach ($manifest['icons'] as $icon) {
            $path = public_path(ltrim($icon['src'], '/'));
            $this->assertFileExists($path);
            [$w, $h, $type] = getimagesize($path);
            $this->assertSame(IMAGETYPE_PNG, $type, $icon['src'].' must be a real PNG');
            $this->assertSame($icon['sizes'], "{$w}x{$h}");
            $sizes[] = $icon['sizes'];
        }
        // Chrome needs 192 and 512 to offer installation.
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
    }

    public function test_service_worker_does_not_cache_pages(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringNotContainsString('caches.open', $sw);
        $this->assertStringNotContainsString('cache.put', $sw);
        $this->assertStringContainsString("request.mode !== 'navigate'", $sw);
    }

    public function test_login_page_links_manifest_and_registers_service_worker(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="manifest" href="/site.webmanifest">', $html);
        $this->assertStringContainsString("register('/sw.js'", $html);
        $this->assertStringContainsString('/icons/apple-touch-icon.png', $html);
    }

    public function test_dashboard_shows_install_banner(): void
    {
        $org = Organization::create([
            'name' => 'App Firm',
            'slug' => 'appfirm',
            'plan' => 'professional',
            'seat_limit' => 10,
        ]);
        $user = User::factory()->create(['role' => 'admin', 'organization_id' => $org->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="install-app-banner"', false)
            ->assertSee('Install VouchEx app')
            ->assertSee('<link rel="manifest" href="/site.webmanifest">', false);
    }
}
