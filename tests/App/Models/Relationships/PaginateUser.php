<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PaginateUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
