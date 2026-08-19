<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class GenericUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];
}
