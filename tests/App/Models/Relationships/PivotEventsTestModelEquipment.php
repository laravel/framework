<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class PivotEventsTestModelEquipment extends MorphPivot
{
    public $table = 'equipmentables';

    public static $eventsMorphClasses = [];

    public static $eventsMorphTypes = [];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            static::$eventsMorphClasses[] = $model->morphClass;
            static::$eventsMorphTypes[] = $model->morphType;
        });

        static::created(function ($model) {
            static::$eventsMorphClasses[] = $model->morphClass;
            static::$eventsMorphTypes[] = $model->morphType;
        });

        static::updating(function ($model) {
            static::$eventsMorphClasses[] = $model->morphClass;
            static::$eventsMorphTypes[] = $model->morphType;
        });

        static::updated(function ($model) {
            static::$eventsMorphClasses[] = $model->morphClass;
            static::$eventsMorphTypes[] = $model->morphType;
        });

        static::saving(function ($model) {
            static::$eventsMorphClasses[] = $model->morphClass;
            static::$eventsMorphTypes[] = $model->morphType;
        });

        static::saved(function ($model) {
            static::$eventsMorphClasses[] = $model->morphClass;
            static::$eventsMorphTypes[] = $model->morphType;
        });

        static::deleting(function ($model) {
            static::$eventsMorphClasses[] = $model->morphClass;
            static::$eventsMorphTypes[] = $model->morphType;
        });

        static::deleted(function ($model) {
            static::$eventsMorphClasses[] = $model->morphClass;
            static::$eventsMorphTypes[] = $model->morphType;
        });
    }

    public function equipment()
    {
        return $this->belongsTo(PivotEventsTestEquipment::class);
    }

    public function equipmentable()
    {
        return $this->morphTo();
    }
}
