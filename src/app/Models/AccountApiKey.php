<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountApiKey extends Model
{
    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'record_unique_identifier',
        'tenant_account_id',
        'api_key_name',
        'gateway_api_key',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->record_unique_identifier)) {
                $model->record_unique_identifier = md5(uniqid(rand(), true));
            }
            if (empty($model->gateway_api_key)) {
                $model->gateway_api_key = md5(rand() . time());
            }
        });
    }

    public function tenant_account()
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }
}
