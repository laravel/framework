<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LazyMorphCountPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    public function likes()
    {
        return $this->hasMany(LazyMorphCountLike::class, 'post_id');
    }
}
