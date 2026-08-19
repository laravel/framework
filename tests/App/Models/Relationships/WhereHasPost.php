<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class WhereHasPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    protected $guarded = [];

    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function texts()
    {
        return $this->hasMany(Text::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(WhereHasUser::class, 'user_id');
    }
}
