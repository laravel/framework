<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class ThroughLike extends Model
{
    protected $table = 'likes';

    public $timestamps = false;

    public function comment()
    {
        return $this->belongsTo(ThroughComment::class);
    }
}
