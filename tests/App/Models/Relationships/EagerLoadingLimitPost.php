<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EagerLoadingLimitPost extends Model
{
    protected $table = 'posts';

    protected $guarded = [];
}
