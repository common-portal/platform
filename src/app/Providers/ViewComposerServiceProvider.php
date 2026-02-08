<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\PlatformSetting;
use App\Models\TenantAccount;

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

        View::composer('layouts.guest', function ($view) {
            $this->composeGuestLayout($view);
        });

        View::composer('components.sidebar-menu', function ($view) {
            $this->composeSidebarMenu($view);
        });

        View::composer('pages.homepage', function ($view) {
            $this->composeHomepage($view);
        });

        View::composer('pages.homepage-guest', function ($view) {
            $this->composeHomepageGuest($view);
        });

        View::composer('pages.homepage-authenticated', function ($view) {
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
        // Check for subdomain tenant branding override
        $subdomainTenant = $this->getSubdomainTenant();
        
        $view->with([
            'platformName' => $subdomainTenant?->account_display_name 
                ?? PlatformSetting::getValue('platform_display_name', 'Common Portal'),
            'platformLogo' => $subdomainTenant?->branding_logo_image_path 
                ?? PlatformSetting::getValue('platform_logo_image_path', '/images/platform-defaults/platform-logo.png'),
            'favicon' => PlatformSetting::getValue('platform_favicon_image_path', '/images/platform-defaults/favicon.png'),
            'metaImage' => PlatformSetting::getValue('social_sharing_preview_image_path', '/images/platform-defaults/meta-card-preview.png'),
            'metaDescription' => PlatformSetting::getValue('social_sharing_meta_description', 'A white-label, multi-tenant portal platform.'),
            'themeColors' => $this->getThemeColors(),
            'subdomainTenant' => $subdomainTenant,
        ]);
    }

    /**
     * Compose guest layout variables (branding for public/guest pages).
     */
    protected function composeGuestLayout($view): void
    {
        $subdomainTenant = $this->getSubdomainTenant();
        
        $view->with([
            'platformName' => $subdomainTenant?->account_display_name 
                ?? PlatformSetting::getValue('platform_display_name', config('app.name', 'xramp.io')),
            'platformLogo' => $subdomainTenant?->branding_logo_image_path 
                ?? PlatformSetting::getValue('platform_logo_image_path', '/images/platform-defaults/platform-logo.png'),
            'favicon' => PlatformSetting::getValue('platform_favicon_image_path', '/images/platform-defaults/favicon.png'),
            'metaImage' => PlatformSetting::getValue('social_sharing_preview_image_path', '/images/platform-defaults/meta-card-preview.png'),
            'metaDescription' => PlatformSetting::getValue('social_sharing_meta_description', 'A white-label, multi-tenant portal platform.'),
            'themeColors' => $this->getThemeColors(),
            'title' => config('app.name', 'xramp.io'),
        ]);
    }

    /**
     * Get subdomain tenant if accessing via whitelabel subdomain.
     */
    protected function getSubdomainTenant(): ?TenantAccount
    {
        $tenantId = session('subdomain_tenant_id');
        
        if (!$tenantId) {
            return null;
        }

        return TenantAccount::where('id', $tenantId)
            ->where('is_soft_deleted', false)
            ->first();
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
        $membership = null;

        if ($user) {
            $userAccounts = $user->tenant_accounts()
                ->where('is_soft_deleted', false)
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
            }
        }

        $menuToggles = PlatformSetting::getValue('sidebar_menu_item_visibility_toggles', []);

        // Pass both: admin-level visibility AND user permissions separately
        // Admin toggles control whether item shows at all
        // User permissions control whether shown item is enabled or disabled
        $view->with([
            'userAccounts' => $userAccounts,
            'activeAccountId' => $activeAccountId,
            'activeAccount' => $activeAccount,
            'membership' => $membership,
            // Admin-level toggles (platform-wide visibility)
            'menuItemEnabled' => [
                'account_settings' => $menuToggles['can_access_account_settings'] ?? true,
                'dashboard' => $menuToggles['can_access_account_dashboard'] ?? true,
                'team' => $menuToggles['can_manage_team_members'] ?? true,
                'developer' => $menuToggles['can_access_developer_tools'] ?? true,
                'support' => $menuToggles['can_access_support_tickets'] ?? true,
                'transactions' => $menuToggles['can_view_transaction_history'] ?? true,
                'billing' => $menuToggles['can_view_billing_history'] ?? true,
                'ibans' => $menuToggles['can_view_ibans'] ?? true,
            ],
            // User-level permissions (team member access)
            'canAccessAccountSettings' => $this->hasUserPermission('can_access_account_settings', $membership, $user),
            'canAccessAccountDashboard' => $this->hasUserPermission('can_access_account_dashboard', $membership, $user),
            'canManageTeamMembers' => $this->hasUserPermission('can_manage_team_members', $membership, $user),
            'canAccessDeveloperTools' => $this->hasUserPermission('can_access_developer_tools', $membership, $user),
            'canAccessSupportTickets' => $this->hasUserPermission('can_access_support_tickets', $membership, $user),
            'canViewTransactionHistory' => $this->hasUserPermission('can_view_transaction_history', $membership, $user),
            'canViewBillingHistory' => $this->hasUserPermission('can_view_billing_history', $membership, $user),
            'canViewIbans' => $this->hasUserPermission('can_view_ibans', $membership, $user),
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
     * Checks: 1) Platform toggle enabled, 2) User permission or admin override
     */
    protected function canAccessMenuItem(string $permission, $membership, array $menuToggles, $user): bool
    {
        // Check platform-level toggle first
        if (!($menuToggles[$permission] ?? true)) {
            return false;
        }

        // Platform admins bypass all permission checks
        if ($user && $user->is_platform_administrator) {
            return true;
        }

        // Check membership permission
        if ($membership) {
            return $membership->hasPermission($permission);
        }

        return false;
    }

    /**
     * Check if user has permission for a menu item (ignores admin toggle).
     * Used for showing disabled state on items user lacks permission for.
     */
    protected function hasUserPermission(string $permission, $membership, $user): bool
    {
        // Platform admins bypass all permission checks
        if ($user && $user->is_platform_administrator) {
            return true;
        }

        // Check membership permission
        if ($membership) {
            return $membership->hasPermission($permission);
        }

        return false;
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
                $teamMemberCount = $activeAccount->platform_members()->count();
            }
        }

        $view->with([
            'activeAccount' => $activeAccount,
            'memberRole' => $memberRole,
            'teamMemberCount' => $teamMemberCount,
        ]);
    }

    /**
     * Compose homepage guest variables (branding for public homepage).
     */
    protected function composeHomepageGuest($view): void
    {
        $subdomainTenant = $this->getSubdomainTenant();
        
        $view->with([
            'platformName' => $subdomainTenant?->account_display_name 
                ?? PlatformSetting::getValue('platform_display_name', config('app.name', 'xramp.io')),
            'platformLogo' => $subdomainTenant?->branding_logo_image_path 
                ?? PlatformSetting::getValue('platform_logo_image_path', '/images/platform-defaults/platform-logo.png'),
            'brandPrimaryColor' => $subdomainTenant?->branding_primary_color 
                ?? PlatformSetting::getValue('primary_brand_color', '#e3be3b'),
        ]);
    }
}
