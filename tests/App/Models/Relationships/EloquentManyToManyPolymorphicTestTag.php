<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentManyToManyPolymorphicTestTag extends Model
{
    protected $table = 'tags';
    protected $guarded = [];

    public function posts()
    {
        return $this->morphedByMany(EloquentManyToManyPolymorphicTestPost::class, 'taggable');
    }

    public function images()
    {
        return $this->morphedByMany(EloquentManyToManyPolymorphicTestImage::class, 'taggable');
    }
}
