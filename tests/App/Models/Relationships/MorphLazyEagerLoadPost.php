<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphLazyEagerLoadPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;
    protected $primaryKey = 'post_id';

    public function user()
    {
        return $this->belongsTo(MorphLazyEagerLoadUser::class);
    }
}
