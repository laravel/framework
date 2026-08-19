<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $fillable = ['name', 'type'];

    public function posts()
    {
        return $this->belongsToMany(TagsPost::class, 'posts_tags', 'tag_id', 'post_id');
    }
}
