<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class NestedEagerLoadComment extends Model
{
    protected $table = 'comments';

    public $timestamps = false;

    protected $guarded = [];

    public function tags()
    {
        return $this->hasMany(NestedEagerLoadTag::class, 'comment_id');
    }
}
