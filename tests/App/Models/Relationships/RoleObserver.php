<?php

namespace Illuminate\Tests\App\Models\Relationships;

class RoleObserver
{
    public static $model;

    public function forceDeleted($model)
    {
        static::$model = $model;
    }
}
