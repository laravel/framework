<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class UniqueTag extends Model
{
    public $table = 'unique_tags';
    public $timestamps = true;
    protected $fillable = ['name', 'type'];

    public function posts()
    {
        return $this->belongsToMany(TagsPost::class, 'posts_unique_tags', 'tag_id', 'post_id');
    }
}
