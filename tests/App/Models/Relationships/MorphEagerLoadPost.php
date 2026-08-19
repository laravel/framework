<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphEagerLoadPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;
    protected $primaryKey = 'post_id';

    public function user()
    {
        return $this->belongsTo(MorphEagerLoadUser::class);
    }
}
