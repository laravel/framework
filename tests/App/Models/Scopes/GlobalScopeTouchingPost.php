<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;

class GlobalScopeTouchingPost extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(GlobalScopeTouchingComment::class, 'post_id');
    }

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('age', function ($builder) {
            $builder->join('comments', 'comments.post_id', '=', 'posts.id');
        });
    }
}
