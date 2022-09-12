<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Str;

trait HasUuidPrimaryKey
{
    protected $uuidVersion = 'v4';

    /**
     * Generate a primary UUID for the model.
     *
     * @return void
     */
    public static function bootHasUuidPrimaryKey()
    {
        static::creating(function (self $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->generatePrimaryKey();
            }
        });
    }

    /**
     * Generate the primary UUID key for the model.
     *
     * @return string
     */
    public function generatePrimaryKey()
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
