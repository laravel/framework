<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Str;

trait HasPrimaryUlid
{
    /**
     * Generate a primary ULID for the model.
     *
     * @return void
     */
    public static function bootHasPrimaryUlid()
    {
        static::creating(function (self $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->generatePrimaryUlid();
            }
        });
    }

    /**
     * Generate the primary ULID key for the model.
     *
     * @return string
     */
    public function generatePrimaryUlid()
    {
        return (string) Str::ulid();
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }
}
