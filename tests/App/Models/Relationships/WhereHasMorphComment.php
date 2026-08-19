<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhereHasMorphComment extends Model
{
    use SoftDeletes;

    protected $table = 'comments';

    public $timestamps = false;

    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function commentableWithConstraint()
    {
        return $this->morphTo('commentable')->where('title', 'bar');
    }

    public function commentableWithOwnerKey()
    {
        return $this->morphTo('commentable', null, null, 'slug');
    }
}
