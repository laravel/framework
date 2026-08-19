<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;

class DateCastingTestModel2 extends Model
{
    public $table = 'test_model2';
    const UPDATED_AT = null;
    protected $guarded = [];

    public $casts = [
        'date_field' => 'date:Y-m',
        'datetime_field' => 'datetime:Y-m H:i',
        'immutable_date_field' => 'date:Y-m',
        'immutable_datetime_field' => 'datetime:Y-m H:i',
    ];
}
