<?php

namespace Illuminate\Tests\Foundation\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $guarded = [];

    public $timestamps = false;
}
