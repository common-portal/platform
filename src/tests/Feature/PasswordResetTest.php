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
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Notification::fake();

        $member = PlatformMember::factory()->create();

        $this->post('/forgot-password', [
            'login_email_address' => $member->login_email_address,
        ]);

        Notification::assertSentTo($member, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Notification::fake();

        $member = PlatformMember::factory()->create();

        $this->post('/forgot-password', [
            'login_email_address' => $member->login_email_address,
        ]);

        Notification::assertSentTo($member, ResetPassword::class, function (object $notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Notification::fake();

        $member = PlatformMember::factory()->create();

        $this->post('/forgot-password', [
            'login_email_address' => $member->login_email_address,
        ]);

        Notification::assertSentTo($member, ResetPassword::class, function (object $notification) use ($member) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'login_email_address' => $member->login_email_address,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasNoErrors();

            return true;
        });
    }
}
