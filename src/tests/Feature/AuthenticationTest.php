<?php

namespace Tests\Feature;

use App\Models\PlatformMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $member = PlatformMember::factory()->create();

        $response = $this->post('/login', [
            'login_email_address' => $member->login_email_address,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $member = PlatformMember::factory()->create();

        $this->post('/login', [
            'login_email_address' => $member->login_email_address,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
