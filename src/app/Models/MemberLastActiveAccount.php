<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLastActiveAccount extends Model
{
    protected $table = 'member_last_active_accounts';

    public $timestamps = false;

    protected $fillable = [
        'platform_member_id',
        'tenant_account_id',
        'updated_at_timestamp',
    ];

    protected $casts = [
        'updated_at_timestamp' => 'datetime',
    ];

    public function platformMember(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'platform_member_id');
    }

    public function tenantAccount(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }

    /**
     * Record or update the last active account for a member.
     */
    public static function remember(int $platformMemberId, int $tenantAccountId): void
    {
        self::updateOrCreate(
            ['platform_member_id' => $platformMemberId],
            ['tenant_account_id' => $tenantAccountId, 'updated_at_timestamp' => now()]
        );
    }

    /**
     * Retrieve the last active account ID for a member, or null if none stored.
     */
    public static function recall(int $platformMemberId): ?int
    {
        return self::where('platform_member_id', $platformMemberId)
            ->value('tenant_account_id');
    }
}
