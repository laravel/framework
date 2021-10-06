<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Str;

trait HasUUID
{
    /**
     * Boot the UUID trait for a model.
     *
     * @return void
     */
    protected static function bootHasUUID()
    {
        static::creating(function ($model) {
            if ($model->getKey() === null) {
                $model->setAttribute($model->getKeyName(), Str::uuid()->toString());
            }
        });
    }

    /**
     * Disables auto-incrementing on model id.
     *
     * @return false
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Set key type to string, instead of integer.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }
}
