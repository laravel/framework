<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphToEagerLoadPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;
}
