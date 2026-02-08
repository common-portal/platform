<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlatformSetting;

class PlatformSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $default_settings = [
            'platform_display_name' => 'Common Portal',
            'platform_logo_image_path' => '/images/platform-defaults/platform-logo.png',
            'platform_favicon_image_path' => '/images/platform-defaults/favicon.png',
            'social_sharing_preview_image_path' => '/images/platform-defaults/meta-card-preview.png',
            'social_sharing_meta_description' => 'Wholesale, High-Volume Crypto<>Fiat Exchange Services',
            'active_theme_preset_name' => 'dark_mode',
            'custom_theme_color_overrides' => json_encode([
                '--sidebar-background-color' => '#1a1a2e',
                '--sidebar-text-color' => '#e0e0e0',
                '--sidebar-hover-background-color' => '#2a2a4e',
                '--brand-primary-color' => '#00ff88',
                '--brand-secondary-color' => '#0088ff',
                '--status-success-color' => '#22c55e',
                '--status-warning-color' => '#eab308',
                '--status-error-color' => '#ef4444',
                '--hyperlink-text-color' => '#3b82f6',
                '--button-background-color' => '#00ff88',
                '--button-text-color' => '#1a1a2e',
            ]),
            'sidebar_menu_item_visibility_toggles' => json_encode([
                'can_access_account_settings' => true,
                'can_access_account_dashboard' => true,
                'can_manage_team_members' => true,
                'can_access_developer_tools' => false,
                'can_access_support_tickets' => false,
                'can_view_transaction_history' => false,
                'can_view_billing_history' => false,
            ]),
        ];

        foreach ($default_settings as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        $this->command->info('Platform settings seeded successfully.');
    }
}
