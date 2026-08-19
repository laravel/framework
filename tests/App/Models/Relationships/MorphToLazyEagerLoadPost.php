<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphToLazyEagerLoadPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;
    protected $primaryKey = 'post_id';
    protected $with = ['user'];

    public function user()
    {
        return $this->belongsTo(MorphToLazyEagerLoadUser::class);
    }
}
