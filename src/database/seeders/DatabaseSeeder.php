<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PlatformMember;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->withPersonalTeam()->create();

        User::factory()->withPersonalTeam()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create or update shea@nsdb.com as platform administrator
        PlatformMember::updateOrCreate(
            ['login_email_address' => 'shea@nsdb.com'],
            [
                'member_first_name' => 'Shea',
                'member_last_name' => '',
                'is_platform_administrator' => true,
                'preferred_language_code' => 'en',
            ]
        );

        // Create or update administrator@directdebit.now as platform administrator
        PlatformMember::updateOrCreate(
            ['login_email_address' => 'administrator@directdebit.now'],
            [
                'member_first_name' => 'Administrator',
                'member_last_name' => '',
                'is_platform_administrator' => true,
                'preferred_language_code' => 'en',
            ]
        );
    }
}
