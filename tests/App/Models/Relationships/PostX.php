<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PostX extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'posts';

    public function comments()
    {
        return $this->hasMany(CommentX::class, 'post_id');
    }
}
