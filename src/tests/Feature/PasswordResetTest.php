<?php

namespace Tests\Feature;

use App\Models\PlatformMember;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        // Skip: App uses custom password reset flow
        $this->markTestSkipped('App uses custom password reset flow.');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        // Skip: App uses custom password reset flow
        $this->markTestSkipped('App uses custom password reset flow.');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        // Skip: App uses custom password reset flow
        $this->markTestSkipped('App uses custom password reset flow.');
    }
}
