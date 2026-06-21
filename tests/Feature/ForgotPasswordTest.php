<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_loads(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot password', false);
    }

    public function test_reset_link_is_sent_for_valid_workspace_and_email(): void
    {
        Notification::fake();

        $org = Organization::create([
            'name' => 'Reset Firm',
            'slug' => 'reset-firm',
            'plan' => 'starter',
            'seat_limit' => 5,
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'email' => 'partner@reset.test',
        ]);

        $this->post(route('password.email'), [
            'workspace' => $org->slug,
            'email' => 'partner@reset.test',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_user_can_reset_password_with_token(): void
    {
        $org = Organization::create([
            'name' => 'Reset Firm',
            'slug' => 'reset-firm-2',
            'plan' => 'starter',
            'seat_limit' => 5,
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'email' => 'reset@reset.test',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'workspace' => $org->slug,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login', ['workspace' => $org->slug]));

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }
}
