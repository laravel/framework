<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\StringStatus;

class EloquentModelEnumCastingUniqueTestModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'unique_enum_casts';

    public $casts = [
        'string_status' => StringStatus::class,
    ];
}
