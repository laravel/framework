<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphLazyEagerLoadUser extends Model
{
    protected $table = 'users';

    public $timestamps = false;
}
