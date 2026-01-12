<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccountWebhook extends Model
{
    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'record_unique_identifier',
        'tenant_account_id',
        'webhook_name',
        'webhook_url',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->record_unique_identifier)) {
                $model->record_unique_identifier = Str::random(32);
            }
        });
    }

    public function tenant_account()
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }
}
