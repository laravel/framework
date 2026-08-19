<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserPostPivot extends Pivot
{
    protected $table = 'users_posts';
}
