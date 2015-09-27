<?php

namespace Illuminate\Database\Eloquent;

trait RefreshOnCreate
{
    /**
     * Boot the refresh on create trait for a model.
     *
     * @return void
     */
    public static function bootRefreshOnCreate()
    {
        static::created(function ($model) {
            static::refresh($model);
        });
    }

    /**
     * Refresh the current model with the current attributes from the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected static function refresh(Model $model)
    {
        $fresh = $model->fresh();

        $model->setRawAttributes($fresh->getAttributes());

        $model->setRelations($fresh->getRelations());
    }
}
