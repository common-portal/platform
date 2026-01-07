<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use App\Traits\HasRecordUniqueIdentifier;

class OneTimePasswordToken extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'one_time_password_tokens';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = null;

    protected $fillable = [
        'record_unique_identifier',
        'platform_member_id',
        'hashed_verification_code',
        'token_expires_at_timestamp',
        'token_used_at_timestamp',
    ];

    protected $hidden = [
        'hashed_verification_code',
    ];

    protected $casts = [
        'token_expires_at_timestamp' => 'datetime',
        'token_used_at_timestamp' => 'datetime',
        'created_at_timestamp' => 'datetime',
    ];

    /**
     * The platform member this token belongs to.
     */
    public function platform_member(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'platform_member_id');
    }

    /**
     * Check if the token is valid (not used and not expired).
     */
    public function isValid(): bool
    {
        return $this->token_used_at_timestamp === null 
            && $this->token_expires_at_timestamp->isFuture();
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        return $this->token_expires_at_timestamp->isPast();
    }

    /**
     * Check if the token has been used.
     */
    public function isUsed(): bool
    {
        return $this->token_used_at_timestamp !== null;
    }

    /**
     * Verify the provided code against the hashed code.
     */
    public function verifyCode(string $plain_code): bool
    {
        return Hash::check($plain_code, $this->hashed_verification_code);
    }

    /**
     * Mark the token as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['token_used_at_timestamp' => now()]);
    }

    /**
     * Create a new OTP token for a member.
     */
    public static function createForMember(PlatformMember $member, int $code_length = 6): array
    {
        $plain_code = str_pad(random_int(0, pow(10, $code_length) - 1), $code_length, '0', STR_PAD_LEFT);

        $token = self::create([
            'platform_member_id' => $member->id,
            'hashed_verification_code' => Hash::make($plain_code),
            'token_expires_at_timestamp' => now()->addHours(72),
        ]);

        return [
            'token' => $token,
            'plain_code' => $plain_code,
        ];
    }

    /**
     * Invalidate all pending tokens for a member.
     */
    public static function invalidateAllForMember(int $platform_member_id): void
    {
        self::where('platform_member_id', $platform_member_id)
            ->whereNull('token_used_at_timestamp')
            ->where('token_expires_at_timestamp', '>', now())
            ->update(['token_used_at_timestamp' => now()]);
    }

    /**
     * Scope: valid tokens only.
     */
    public function scopeValid($query)
    {
        return $query->whereNull('token_used_at_timestamp')
            ->where('token_expires_at_timestamp', '>', now());
    }
}
