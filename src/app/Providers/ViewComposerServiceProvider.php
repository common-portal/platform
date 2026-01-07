<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\PlatformSetting;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('layouts.platform', function ($view) {
            $this->composePlatformLayout($view);
        });

        View::composer('components.sidebar-menu', function ($view) {
            $this->composeSidebarMenu($view);
        });

        View::composer('pages.homepage', function ($view) {
            $this->composeHomepage($view);
        });

        View::composer('components.language-selector', function ($view) {
            $this->composeLanguageSelector($view);
        });
    }

    /**
     * Compose platform layout variables.
     */
    protected function composePlatformLayout($view): void
    {
        $view->with([
            'platformName' => PlatformSetting::getValue('platform_display_name', 'Common Portal'),
            'platformLogo' => PlatformSetting::getValue('platform_logo_image_path', '/images/platform-defaults/platform-logo.png'),
            'favicon' => PlatformSetting::getValue('platform_favicon_image_path', '/images/platform-defaults/favicon.png'),
            'metaImage' => PlatformSetting::getValue('social_sharing_preview_image_path', '/images/platform-defaults/meta-card-preview.png'),
            'metaDescription' => PlatformSetting::getValue('social_sharing_meta_description', 'A white-label, multi-tenant portal platform.'),
            'themeColors' => $this->getThemeColors(),
        ]);
    }

    /**
     * Compose sidebar menu variables.
     */
    protected function composeSidebarMenu($view): void
    {
        $user = auth()->user();
        $activeAccountId = session('active_account_id');
        $userAccounts = collect();
        $activeAccount = null;
        $permissions = [];

        if ($user) {
            $userAccounts = $user->tenant_accounts()
                ->wherePivot('membership_status', 'membership_active')
                ->get();

            if (!$activeAccountId && $userAccounts->isNotEmpty()) {
                $activeAccountId = $userAccounts->first()->id;
                session(['active_account_id' => $activeAccountId]);
            }

            $activeAccount = $userAccounts->firstWhere('id', $activeAccountId);

            if ($activeAccount) {
                $membership = $user->account_memberships()
                    ->where('tenant_account_id', $activeAccountId)
                    ->first();
                
                if ($membership) {
                    $permissions = $membership->granted_permission_slugs ?? [];
                }
            }
        }

        $menuToggles = json_decode(
            PlatformSetting::getValue('sidebar_menu_item_visibility_toggles', '{}'),
            true
        ) ?? [];

        $view->with([
            'userAccounts' => $userAccounts,
            'activeAccountId' => $activeAccountId,
            'activeAccount' => $activeAccount,
            'canAccessAccountSettings' => $this->canAccessMenuItem('can_access_account_settings', $permissions, $menuToggles, $user),
            'canAccessAccountDashboard' => $this->canAccessMenuItem('can_access_account_dashboard', $permissions, $menuToggles, $user),
            'canManageTeamMembers' => $this->canAccessMenuItem('can_manage_team_members', $permissions, $menuToggles, $user),
            'canAccessDeveloperTools' => $this->canAccessMenuItem('can_access_developer_tools', $permissions, $menuToggles, $user),
            'canAccessSupportTickets' => $this->canAccessMenuItem('can_access_support_tickets', $permissions, $menuToggles, $user),
        ]);
    }

    /**
     * Compose language selector variables.
     */
    protected function composeLanguageSelector($view): void
    {
        $preferredLanguage = 'eng';
        
        if (auth()->check()) {
            $preferredLanguage = auth()->user()->preferred_language_code ?? 'eng';
        } else {
            $preferredLanguage = session('preferred_language', 'eng');
        }

        $view->with([
            'preferredLanguage' => $preferredLanguage,
        ]);
    }

    /**
     * Get theme colors from platform settings.
     */
    protected function getThemeColors(): array
    {
        $overrides = json_decode(
            PlatformSetting::getValue('custom_theme_color_overrides', '{}'),
            true
        ) ?? [];

        return $overrides;
    }

    /**
     * Check if user can access a menu item.
     */
    protected function canAccessMenuItem(string $permission, array $userPermissions, array $menuToggles, $user): bool
    {
        if (!($menuToggles[$permission] ?? true)) {
            return false;
        }

        if ($user && $user->is_platform_administrator) {
            return true;
        }

        return in_array($permission, $userPermissions);
    }

    /**
     * Compose homepage variables.
     */
    protected function composeHomepage($view): void
    {
        $user = auth()->user();
        $activeAccountId = session('active_account_id');
        $activeAccount = null;
        $memberRole = null;
        $teamMemberCount = 1;

        if ($user && $activeAccountId) {
            $activeAccount = $user->tenant_accounts()
                ->where('tenant_accounts.id', $activeAccountId)
                ->first();

            if ($activeAccount) {
                $memberRole = $activeAccount->pivot->account_membership_role ?? 'account_team_member';
                $teamMemberCount = $activeAccount->members()->count();
            }
        }

        $view->with([
            'activeAccount' => $activeAccount,
            'memberRole' => $memberRole,
            'teamMemberCount' => $teamMemberCount,
        ]);
    }
}
