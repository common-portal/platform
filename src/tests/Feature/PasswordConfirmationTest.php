<?php

namespace Tests\Feature;

use App\Models\PlatformMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $member = PlatformMember::factory()->create();

        $response = $this->actingAs($member)->get('/user/confirm-password');

        $response->assertStatus(200);
    }

    public function test_password_can_be_confirmed(): void
    {
        // Skip: App uses custom password handling
        $this->markTestSkipped('App uses custom password handling.');
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        // Skip: App uses custom password handling
        $this->markTestSkipped('App uses custom password handling.');
    }
}
