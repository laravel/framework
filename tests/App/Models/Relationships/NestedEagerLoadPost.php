<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class NestedEagerLoadPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(NestedEagerLoadComment::class, 'post_id');
    }

    public function image()
    {
        return $this->hasOne(Image::class, 'post_id');
    }
}
