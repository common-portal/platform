<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasRecordUniqueIdentifier;

class TenantAccount extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'tenant_accounts';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'record_unique_identifier',
        'account_display_name',
        'account_type',
        'whitelabel_subdomain_slug',
        'branding_logo_image_path',
        'primary_contact_full_name',
        'primary_contact_email_address',
        'customer_support_email',
        'is_soft_deleted',
        'soft_deleted_at_timestamp',
    ];

    protected $casts = [
        'is_soft_deleted' => 'boolean',
        'soft_deleted_at_timestamp' => 'datetime',
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    /**
     * Members belonging to this account.
     */
    public function platform_members(): BelongsToMany
    {
        return $this->belongsToMany(
            PlatformMember::class,
            'tenant_account_memberships',
            'tenant_account_id',
            'platform_member_id'
        )->withPivot([
            'account_membership_role',
            'granted_permission_slugs',
            'membership_status',
            'membership_accepted_at_timestamp',
            'membership_revoked_at_timestamp',
        ]);
    }

    /**
     * Memberships for this account.
     */
    public function account_memberships(): HasMany
    {
        return $this->hasMany(TenantAccountMembership::class, 'tenant_account_id');
    }

    /**
     * Invitations for this account.
     */
    public function team_membership_invitations(): HasMany
    {
        return $this->hasMany(TeamMembershipInvitation::class, 'tenant_account_id');
    }

    /**
     * Support tickets for this account.
     */
    public function support_tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'tenant_account_id');
    }

    /**
     * Get active members only.
     */
    public function active_members(): BelongsToMany
    {
        return $this->platform_members()
            ->wherePivot('membership_status', 'membership_active');
    }

    /**
     * Get the account owner.
     */
    public function owner(): ?PlatformMember
    {
        return $this->platform_members()
            ->wherePivot('account_membership_role', 'account_owner')
            ->first();
    }

    /**
     * Check if this is a personal account.
     */
    public function isPersonalAccount(): bool
    {
        return $this->account_type === 'personal_individual';
    }

    /**
     * Check if this is a business account.
     */
    public function isBusinessAccount(): bool
    {
        return $this->account_type === 'business_organization';
    }

    /**
     * Scope: exclude soft deleted accounts.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_soft_deleted', false);
    }

    /**
     * Soft delete the account.
     */
    public function softDelete(): void
    {
        if ($this->isPersonalAccount()) {
            throw new \Exception('Personal accounts cannot be deleted.');
        }

        $this->update([
            'is_soft_deleted' => true,
            'soft_deleted_at_timestamp' => now(),
        ]);
    }
}
