<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MorphEagerLoadUser extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    public $timestamps = false;
}
