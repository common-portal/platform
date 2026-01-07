<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMembershipInvitation extends Model
{
    use HasFactory;

    protected $table = 'team_membership_invitations';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'tenant_account_id',
        'invited_email_address',
        'invited_by_member_id',
        'invitation_status',
        'invitation_resend_count',
        'invitation_last_sent_at_timestamp',
        'invitation_accepted_at_timestamp',
    ];

    protected $casts = [
        'invitation_resend_count' => 'integer',
        'invitation_last_sent_at_timestamp' => 'datetime',
        'invitation_accepted_at_timestamp' => 'datetime',
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    /**
     * The tenant account this invitation is for.
     */
    public function tenant_account(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }

    /**
     * The member who sent the invitation.
     */
    public function invited_by_member(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'invited_by_member_id');
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->invitation_status === 'invitation_pending';
    }

    /**
     * Check if invitation is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->invitation_status === 'invitation_accepted';
    }

    /**
     * Check if invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->invitation_status === 'invitation_expired';
    }

    /**
     * Mark invitation as accepted.
     */
    public function accept(): void
    {
        $this->update([
            'invitation_status' => 'invitation_accepted',
            'invitation_accepted_at_timestamp' => now(),
        ]);
    }

    /**
     * Mark invitation as expired.
     */
    public function expire(): void
    {
        $this->update([
            'invitation_status' => 'invitation_expired',
        ]);
    }

    /**
     * Increment resend count and update timestamp.
     */
    public function recordResend(): void
    {
        $this->update([
            'invitation_resend_count' => $this->invitation_resend_count + 1,
            'invitation_last_sent_at_timestamp' => now(),
        ]);
    }

    /**
     * Scope: pending invitations only.
     */
    public function scopePending($query)
    {
        return $query->where('invitation_status', 'invitation_pending');
    }

    /**
     * Scope: by email address.
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('invited_email_address', $email);
    }
}
