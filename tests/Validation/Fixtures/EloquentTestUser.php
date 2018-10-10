<?php

namespace Illuminate\Tests\Validation\Fixtures;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EloquentTestUser extends Eloquent
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
