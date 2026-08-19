<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentManyToManyPolymorphicTestPost extends Model
{
    protected $table = 'posts';
    protected $guarded = [];

    public function tags()
    {
        return $this->morphToMany(EloquentManyToManyPolymorphicTestTag::class, 'taggable');
    }
}
