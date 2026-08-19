<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;

class CustomPivotCastTestUser extends Model
{
    public $table = 'users';
    public $timestamps = false;
}
