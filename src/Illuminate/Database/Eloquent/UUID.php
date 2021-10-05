<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Str;

trait UUID
{
    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
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
