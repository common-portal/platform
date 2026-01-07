<?php

namespace App\Traits;

trait HasRecordUniqueIdentifier
{
    /**
     * Boot the trait.
     */
    protected static function bootHasRecordUniqueIdentifier(): void
    {
        static::creating(function ($model) {
            if (empty($model->record_unique_identifier)) {
                $model->record_unique_identifier = self::generateRecordUniqueIdentifier();
            }
        });
    }

    /**
     * Generate a unique identifier using md5(random + microtime).
     */
    public static function generateRecordUniqueIdentifier(): string
    {
        return md5(random_int(100000, 999999) . microtime(true) . uniqid('', true));
    }

    /**
     * Find a record by its unique identifier.
     */
    public static function findByUniqueIdentifier(string $identifier): ?static
    {
        return static::where('record_unique_identifier', $identifier)->first();
    }
}
