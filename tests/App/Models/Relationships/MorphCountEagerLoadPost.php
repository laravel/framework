<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphCountEagerLoadPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    public function likes()
    {
        return $this->hasMany(MorphCountEagerLoadLike::class, 'post_id');
    }
}
