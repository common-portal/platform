<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlatformMember extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'platform_members';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'login_email_address',
        'hashed_login_password',
        'member_first_name',
        'member_last_name',
        'profile_avatar_image_path',
        'preferred_language_code',
        'is_platform_administrator',
        'email_verified_at_timestamp',
    ];

    protected $hidden = [
        'hashed_login_password',
    ];

    protected $casts = [
        'is_platform_administrator' => 'boolean',
        'email_verified_at_timestamp' => 'datetime',
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    /**
     * Get the password attribute for Laravel auth.
     */
    public function getAuthPassword(): string
    {
        return $this->hashed_login_password ?? '';
    }

    /**
     * Get the email attribute for Laravel auth.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->login_email_address;
    }

    /**
     * Tenant accounts this member belongs to.
     */
    public function tenant_accounts(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantAccount::class,
            'tenant_account_memberships',
            'platform_member_id',
            'tenant_account_id'
        )->withPivot([
            'account_membership_role',
            'granted_permission_slugs',
            'membership_status',
            'membership_accepted_at_timestamp',
            'membership_revoked_at_timestamp',
        ]);
    }

    /**
     * Account memberships for this member.
     */
    public function account_memberships(): HasMany
    {
        return $this->hasMany(TenantAccountMembership::class, 'platform_member_id');
    }

    /**
     * OTP tokens for this member.
     */
    public function one_time_password_tokens(): HasMany
    {
        return $this->hasMany(OneTimePasswordToken::class, 'platform_member_id');
    }

    /**
     * Invitations sent by this member.
     */
    public function sent_invitations(): HasMany
    {
        return $this->hasMany(TeamMembershipInvitation::class, 'invited_by_member_id');
    }

    /**
     * Support tickets created by this member.
     */
    public function created_support_tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'created_by_member_id');
    }

    /**
     * Support tickets assigned to this member (as admin).
     */
    public function assigned_support_tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to_administrator_id');
    }

    /**
     * Get the member's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->member_first_name} {$this->member_last_name}") ?: $this->login_email_address;
    }

    /**
     * Get the member's personal account.
     */
    public function personal_account(): ?TenantAccount
    {
        return $this->tenant_accounts()
            ->where('account_type', 'personal_individual')
            ->wherePivot('account_membership_role', 'account_owner')
            ->first();
    }

    /**
     * Check if member has a specific permission in a given account.
     */
    public function hasPermissionInAccount(string $permission_slug, int $tenant_account_id): bool
    {
        if ($this->is_platform_administrator) {
            return true;
        }

        $membership = $this->account_memberships()
            ->where('tenant_account_id', $tenant_account_id)
            ->where('membership_status', 'membership_active')
            ->first();

        if (!$membership) {
            return false;
        }

        $permissions = $membership->granted_permission_slugs ?? [];
        return in_array($permission_slug, $permissions);
    }
}
