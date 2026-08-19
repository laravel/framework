<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\ArrayableStatus;
use Illuminate\Tests\Integration\Database\IntegerStatus;
use Illuminate\Tests\Integration\Database\StringStatus;

class EloquentModelEnumCastingTestModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'enum_casts';

    public $casts = [
        'string_status' => StringStatus::class,
        'string_status_collection' => AsEnumCollection::class.':'.StringStatus::class,
        'string_status_array' => AsEnumArrayObject::class.':'.StringStatus::class,
        'integer_status' => IntegerStatus::class,
        'integer_status_collection' => AsEnumCollection::class.':'.IntegerStatus::class,
        'integer_status_array' => AsEnumArrayObject::class.':'.IntegerStatus::class,
        'arrayable_status' => ArrayableStatus::class,
    ];
}
