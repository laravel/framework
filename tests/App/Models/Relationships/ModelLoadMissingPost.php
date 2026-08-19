<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class ModelLoadMissingPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(ModelLoadMissingComment::class, 'post_id');
    }

    public function firstComment()
    {
        return $this->belongsTo(ModelLoadMissingComment::class, 'first_comment_id');
    }
}
