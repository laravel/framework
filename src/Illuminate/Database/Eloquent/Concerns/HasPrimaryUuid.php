<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Str;

trait HasPrimaryUuid
{
    /**
     * Generate a primary UUID for the model.
     *
     * @return void
     */
    public static function bootHasPrimaryUuid()
    {
        static::creating(function (self $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->generatePrimaryUuid();
            }
        });
    }

    /**
     * Generate the primary UUID key for the model.
     *
     * @return string
     */
    public function generatePrimaryUuid()
    {
        return (string) Str::orderedUuid();
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
