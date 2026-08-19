<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentManyToManyPolymorphicTestImage extends Model
{
    protected $table = 'images';
    protected $guarded = [];

    public function tags()
    {
        return $this->morphToMany(EloquentManyToManyPolymorphicTestTag::class, 'taggable');
    }
}
