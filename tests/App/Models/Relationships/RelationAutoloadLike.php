<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class RelationAutoloadLike extends Model
{
    protected $table = 'likes';

    public $timestamps = false;

    protected $guarded = [];

    public function likeable()
    {
        return $this->morphTo();
    }
}
