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
        if ($this->account_membership_role === 'account_owner') {
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
}
