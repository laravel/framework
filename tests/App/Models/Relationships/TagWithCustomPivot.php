<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class TagWithCustomPivot extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = [];

    public function posts()
    {
        return $this->belongsToMany(TagsPost::class, 'posts_tags', 'tag_id', 'post_id');
    }
}
