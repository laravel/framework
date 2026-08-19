<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EagerLoadingLimitComment extends Model
{
    protected $table = 'comments';

    public $timestamps = false;

    protected $guarded = [];
}
