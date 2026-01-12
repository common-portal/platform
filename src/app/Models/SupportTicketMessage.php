<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasRecordUniqueIdentifier;

class SupportTicketMessage extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'support_ticket_messages';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'record_unique_identifier',
        'support_ticket_id',
        'author_member_id',
        'author_admin_id',
        'message_type',
        'message_body',
    ];

    protected $casts = [
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    /**
     * The ticket this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * The member who authored this message (if member reply).
     */
    public function author_member(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'author_member_id');
    }

    /**
     * The admin who authored this message (if admin response).
     */
    public function author_admin(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'author_admin_id');
    }

    /**
     * Get the author name regardless of type.
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->message_type === 'admin_response' && $this->author_admin) {
            return $this->author_admin->full_name . ' (Support)';
        }
        if ($this->author_member) {
            return $this->author_member->full_name;
        }
        return 'System';
    }

    /**
     * Check if this is a member reply.
     */
    public function isMemberReply(): bool
    {
        return $this->message_type === 'member_reply';
    }

    /**
     * Check if this is an admin response.
     */
    public function isAdminResponse(): bool
    {
        return $this->message_type === 'admin_response';
    }
}
