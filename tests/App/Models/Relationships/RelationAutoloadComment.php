<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class RelationAutoloadComment extends Model
{
    protected $table = 'comments';

    public $timestamps = false;

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    public function likes()
    {
        return $this->morphMany(RelationAutoloadLike::class, 'likeable');
    }

    public function commentable()
    {
        return $this->morphTo();
    }
}
