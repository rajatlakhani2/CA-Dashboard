<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\UserTimezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_user_timezone_when_valid(): void
    {
        $user = User::factory()->make(['timezone' => 'Asia/Kolkata']);

        $this->assertSame('Asia/Kolkata', UserTimezone::for($user));
    }

    public function test_falls_back_to_app_timezone_when_user_timezone_invalid(): void
    {
        config(['app.timezone' => 'UTC']);
        $user = User::factory()->make(['timezone' => 'Not/A/Timezone']);

        $this->assertSame('UTC', UserTimezone::for($user));
    }

    public function test_falls_back_to_asia_kolkata_when_nothing_configured(): void
    {
        config(['app.timezone' => '']);
        $user = User::factory()->make(['timezone' => null]);

        $this->assertSame('Asia/Kolkata', UserTimezone::for($user));
    }
}
