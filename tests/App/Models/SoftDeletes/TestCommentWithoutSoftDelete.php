<?php

namespace Illuminate\Tests\App\Models\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class TestCommentWithoutSoftDelete extends Model
{
    protected $table = 'comments';
    protected $guarded = [];

    public function owner()
    {
        return $this->morphTo();
    }
}
