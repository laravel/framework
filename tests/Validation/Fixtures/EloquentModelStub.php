<?php

namespace Illuminate\Tests\Validation\Fixtures;

use Illuminate\Database\Eloquent\Model;

class EloquentModelStub extends Model
{
    protected $primaryKey = 'id_column';

    protected $guarded = [];
}
