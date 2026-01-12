<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SupportTicketAttachment extends Model
{
    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'record_unique_identifier',
        'support_ticket_id',
        'uploaded_by_member_hash',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_mime_type',
        'file_size_bytes',
        'is_public_submission',
    ];

    protected $casts = [
        'is_public_submission' => 'boolean',
        'file_size_bytes' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->record_unique_identifier)) {
                $model->record_unique_identifier = md5(uniqid(rand(), true));
            }
        });
    }

    public function support_ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function uploaded_by()
    {
        return $this->belongsTo(PlatformMember::class, 'uploaded_by_member_hash', 'record_unique_identifier');
    }

    /**
     * Store an uploaded file for an authenticated user's support ticket.
     * Filename format: {original_filename}_{member_hash}_{account_hash}_{datetime}.{extension}
     */
    public static function storeAuthenticatedFile(UploadedFile $file, int $ticketId, string $accountHash, string $memberHash): self
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $datetime = now()->format('YmdHis');
        
        // Sanitize original filename
        $originalName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $originalName = substr($originalName, 0, 100); // Limit length
        
        // Full hashes in filename: originalname_memberhash_accounthash_datetime.ext
        $storedFilename = "{$originalName}_{$memberHash}_{$accountHash}_{$datetime}.{$extension}";
        $path = $file->storeAs('support-attachments/tickets', $storedFilename, 'public');

        return self::create([
            'support_ticket_id' => $ticketId,
            'uploaded_by_member_hash' => $memberHash,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $storedFilename,
            'file_path' => $path,
            'file_mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'is_public_submission' => false,
        ]);
    }

    /**
     * Store an uploaded file for a public support submission.
     * Filename format: {original_filename}_{md5(rand())}_{datetime}.{extension}
     */
    public static function storePublicFile(UploadedFile $file, ?int $ticketId = null): self
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $datetime = now()->format('YmdHis');
        $randomHash = md5(rand() . time());
        
        // Sanitize original filename
        $originalName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $originalName = substr($originalName, 0, 50); // Limit length
        
        $storedFilename = "{$originalName}_{$randomHash}_{$datetime}.{$extension}";
        $path = $file->storeAs('support-attachments/public', $storedFilename, 'public');

        return self::create([
            'support_ticket_id' => $ticketId,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $storedFilename,
            'file_path' => $path,
            'file_mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'is_public_submission' => true,
        ]);
    }

    /**
     * Get the full URL to the attachment.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
