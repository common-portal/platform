<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasRecordUniqueIdentifier;

class TenantAccountMembership extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'tenant_account_memberships';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'record_unique_identifier',
        'tenant_account_id',
        'platform_member_id',
        'account_membership_role',
        'granted_permission_slugs',
        'membership_status',
        'membership_accepted_at_timestamp',
        'membership_revoked_at_timestamp',
    ];

    protected $casts = [
        'granted_permission_slugs' => 'array',
        'membership_accepted_at_timestamp' => 'datetime',
        'membership_revoked_at_timestamp' => 'datetime',
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    /**
     * The tenant account.
     */
    public function tenant_account(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }

    /**
     * The platform member.
     */
    public function platform_member(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'platform_member_id');
    }

    /**
     * Check if membership is active.
     */
    public function isActive(): bool
    {
        return $this->membership_status === 'membership_active';
    }

    /**
     * Check if membership is awaiting acceptance.
     */
    public function isAwaitingAcceptance(): bool
    {
        return $this->membership_status === 'awaiting_acceptance';
    }

    /**
     * Check if membership is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->membership_status === 'membership_revoked';
    }

    /**
     * Accept the membership invitation.
     */
    public function accept(): void
    {
        $this->update([
            'membership_status' => 'membership_active',
            'membership_accepted_at_timestamp' => now(),
        ]);
    }

    /**
     * Revoke the membership.
     */
    public function revoke(): void
    {
        $this->update([
            'membership_status' => 'membership_revoked',
            'membership_revoked_at_timestamp' => now(),
        ]);
    }

    /**
     * Re-enable a revoked membership.
     */
    public function reactivate(): void
    {
        $this->update([
            'membership_status' => 'membership_active',
            'membership_revoked_at_timestamp' => null,
        ]);
    }

    /**
     * Check if member has a specific permission.
     */
    public function hasPermission(string $permission_slug): bool
    {
        if (in_array($this->account_membership_role, ['account_owner', 'account_administrator'])) {
            return true;
        }

        return in_array($permission_slug, $this->granted_permission_slugs ?? []);
    }

    /**
     * Grant a permission.
     */
    public function grantPermission(string $permission_slug): void
    {
        $permissions = $this->granted_permission_slugs ?? [];
        if (!in_array($permission_slug, $permissions)) {
            $permissions[] = $permission_slug;
            $this->update(['granted_permission_slugs' => $permissions]);
        }
    }

    /**
     * Revoke a permission.
     */
    public function revokePermission(string $permission_slug): void
    {
        $permissions = $this->granted_permission_slugs ?? [];
        $permissions = array_filter($permissions, fn($p) => $p !== $permission_slug);
        $this->update(['granted_permission_slugs' => array_values($permissions)]);
    }

    /**
     * Scope: active memberships only.
     */
    public function scopeActive($query)
    {
        return $query->where('membership_status', 'membership_active');
    }

    /**
     * Check if member can manage team (owner, admin, or has permission).
     */
    public function canManageTeam(): bool
    {
        return $this->hasPermission('can_manage_team_members');
    }

    /**
     * Update permissions with self-protection check.
     * A member cannot remove their own can_manage_team_members permission.
     */
    public function updatePermissions(array $newPermissions, int $editorMemberId): array
    {
        $isSelf = $this->platform_member_id === $editorMemberId;
        $currentHasTeamAccess = $this->hasPermission('can_manage_team_members');
        $newHasTeamAccess = in_array('can_manage_team_members', $newPermissions);

        // Self-protection: cannot remove own team access
        if ($isSelf && $currentHasTeamAccess && !$newHasTeamAccess) {
            return [
                'success' => false,
                'message' => 'You cannot remove your own team management access. Ask another team manager.',
            ];
        }

        $this->update(['granted_permission_slugs' => $newPermissions]);

        return ['success' => true, 'message' => 'Permissions updated successfully.'];
    }

    /**
     * Get all available permission slugs.
     */
    public static function allPermissionSlugs(): array
    {
        return [
            'can_access_account_settings',
            'can_access_account_dashboard',
            'can_view_transaction_history',
            'can_view_billing_history',
            'can_view_ibans',
            'can_view_wallets',
            'can_access_developer_tools',
            'can_manage_team_members',
            'can_access_support_tickets',
        ];
    }

    /**
     * Get human-readable labels for permissions.
     */
    public static function permissionLabels(): array
    {
        return [
            'can_access_account_settings' => 'Account Settings',
            'can_access_account_dashboard' => 'Dashboard',
            'can_view_transaction_history' => 'Transactions',
            'can_view_billing_history' => 'Billing',
            'can_view_ibans' => 'IBANs',
            'can_view_wallets' => 'Wallets',
            'can_access_developer_tools' => 'Developer',
            'can_manage_team_members' => 'Team Members',
            'can_access_support_tickets' => 'Support Tickets',
        ];
    }

    /**
     * Get default permissions for new team members.
     */
    public static function defaultTeamMemberPermissions(): array
    {
        return [
            'can_access_account_dashboard',
        ];
    }

    /**
     * Get full permissions for owners/admins.
     */
    public static function fullPermissions(): array
    {
        return self::allPermissionSlugs();
    }
}
