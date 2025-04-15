<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentModelWithAppendedPropertyHooks extends Model
{
    protected $table = 'test';

    protected $appends = [
        'baz_quz',
    ];

    protected $bazQuz {
        get => 'baz_QUZ';
    }
}
