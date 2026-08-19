<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadCountLike extends Model
{
    protected $table = 'likes';

    public $timestamps = false;
}
