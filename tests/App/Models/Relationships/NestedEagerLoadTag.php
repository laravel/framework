<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class NestedEagerLoadTag extends Model
{
    protected $table = 'tags';

    protected $guarded = [];

    public $timestamps = false;
}
