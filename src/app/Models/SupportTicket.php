<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasRecordUniqueIdentifier;

class SupportTicket extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'support_tickets';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'record_unique_identifier',
        'tenant_account_id',
        'created_by_member_id',
        'ticket_subject_line',
        'ticket_description_body',
        'ticket_status',
        'assigned_to_administrator_id',
    ];

    protected $casts = [
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    /**
     * The tenant account this ticket belongs to.
     */
    public function tenant_account(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }

    /**
     * The member who created the ticket.
     */
    public function created_by_member(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'created_by_member_id');
    }

    /**
     * The administrator assigned to the ticket.
     */
    public function assigned_to_administrator(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'assigned_to_administrator_id');
    }

    /**
     * Check if ticket is open.
     */
    public function isOpen(): bool
    {
        return $this->ticket_status === 'ticket_open';
    }

    /**
     * Check if ticket is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->ticket_status === 'ticket_in_progress';
    }

    /**
     * Check if ticket is resolved.
     */
    public function isResolved(): bool
    {
        return $this->ticket_status === 'ticket_resolved';
    }

    /**
     * Check if ticket is closed.
     */
    public function isClosed(): bool
    {
        return $this->ticket_status === 'ticket_closed';
    }

    /**
     * Set ticket status.
     */
    public function setStatus(string $status): void
    {
        $valid_statuses = ['ticket_open', 'ticket_in_progress', 'ticket_resolved', 'ticket_closed'];
        
        if (!in_array($status, $valid_statuses)) {
            throw new \InvalidArgumentException("Invalid ticket status: {$status}");
        }

        $this->update(['ticket_status' => $status]);
    }

    /**
     * Assign ticket to an administrator.
     */
    public function assignTo(PlatformMember $administrator): void
    {
        if (!$administrator->is_platform_administrator) {
            throw new \InvalidArgumentException('Tickets can only be assigned to platform administrators.');
        }

        $this->update([
            'assigned_to_administrator_id' => $administrator->id,
            'ticket_status' => 'ticket_in_progress',
        ]);
    }

    /**
     * Unassign the ticket.
     */
    public function unassign(): void
    {
        $this->update([
            'assigned_to_administrator_id' => null,
            'ticket_status' => 'ticket_open',
        ]);
    }

    /**
     * Scope: open or in-progress tickets.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('ticket_status', ['ticket_open', 'ticket_in_progress']);
    }

    /**
     * Scope: by account.
     */
    public function scopeForAccount($query, int $tenant_account_id)
    {
        return $query->where('tenant_account_id', $tenant_account_id);
    }
}
