<?php

namespace Illuminate\Tests\Integration\Database\Fixtures;

use Illuminate\Database\Eloquent\Model;

class JsonObject extends Model
{
    public $timestamps = false;

    protected $casts = [
        'sample_data' => 'object',
    ];
}
