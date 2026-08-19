<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadMissingPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(LoadMissingComment::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(LoadMissingUser::class);
    }

    public function postRelation()
    {
        return $this->hasOne(PostRelation::class, 'post_id');
    }
}
