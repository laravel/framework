<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class UserWithCreatedAndUpdated extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
