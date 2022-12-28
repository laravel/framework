<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Str;

trait ObservesEvents
{
    public static function bootObservesEvents()
    {
        $events = [
            'retrieved', 'creating', 'created', 'updating', 'updated',
            'saving', 'saved', 'deleting', 'deleted', 'trashed',
            'forceDeleted', 'restoring', 'restored', 'replicating'
        ];

        foreach ($events as $event) {
            if (!method_exists(static::class, $event)) {
                continue;
            }

            $method = 'on' . Str::studly($event);

            if (!method_exists(static::class, $method)) {
                continue;
            }

            static::$event(function ($model) use ($method) {
                $model->$method();
            });
        }
    }
}
