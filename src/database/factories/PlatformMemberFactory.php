<?php

namespace Database\Factories;

use App\Models\PlatformMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlatformMember>
 */
class PlatformMemberFactory extends Factory
{
    protected $model = PlatformMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'record_unique_identifier' => Str::uuid()->toString(),
            'login_email_address' => fake()->unique()->safeEmail(),
            'hashed_login_password' => Hash::make('password'),
            'member_first_name' => fake()->firstName(),
            'member_last_name' => fake()->lastName(),
            'profile_avatar_image_path' => null,
            'preferred_language_code' => 'en',
            'is_platform_administrator' => false,
            'email_verified_at_timestamp' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at_timestamp' => null,
        ]);
    }

    /**
     * Indicate that the member is a platform administrator.
     */
    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_platform_administrator' => true,
        ]);
    }
}
