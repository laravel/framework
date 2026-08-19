<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LazyMorphCountLike extends Model
{
    protected $table = 'likes';

    public $timestamps = false;

    public function post()
    {
        return $this->belongsTo(LazyMorphCountPost::class);
    }
}
