<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;

class TestModelCustomImmutable extends Model
{
    public $table = 'test_model_immutable';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'date_field' => 'immutable_date:Y-m',
        'datetime_field' => 'immutable_datetime:Y-m H:i',
    ];
}
