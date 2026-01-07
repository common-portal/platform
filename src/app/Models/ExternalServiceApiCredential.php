<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ExternalServiceApiCredential extends Model
{
    use HasFactory;

    protected $table = 'external_service_api_credentials';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'external_service_name',
        'encrypted_api_key',
        'is_currently_active_service',
    ];

    protected $hidden = [
        'encrypted_api_key',
    ];

    protected $casts = [
        'is_currently_active_service' => 'boolean',
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    /**
     * Get the decrypted API key.
     */
    public function getDecryptedApiKey(): string
    {
        return Crypt::decryptString($this->encrypted_api_key);
    }

    /**
     * Set the API key (encrypts automatically).
     */
    public function setApiKey(string $plain_key): void
    {
        $this->update([
            'encrypted_api_key' => Crypt::encryptString($plain_key),
        ]);
    }

    /**
     * Get the currently active service for a given name.
     */
    public static function getActiveService(string $service_name): ?self
    {
        return self::where('external_service_name', $service_name)
            ->where('is_currently_active_service', true)
            ->first();
    }

    /**
     * Set this service as active and deactivate others of same type.
     */
    public function setAsActive(): void
    {
        self::where('external_service_name', $this->external_service_name)
            ->update(['is_currently_active_service' => false]);

        $this->update(['is_currently_active_service' => true]);
    }

    /**
     * Create a new credential with encrypted key.
     */
    public static function createWithEncryption(string $service_name, string $plain_key, bool $active = false): self
    {
        return self::create([
            'external_service_name' => $service_name,
            'encrypted_api_key' => Crypt::encryptString($plain_key),
            'is_currently_active_service' => $active,
        ]);
    }

    /**
     * Scope: active services only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_currently_active_service', true);
    }
}
