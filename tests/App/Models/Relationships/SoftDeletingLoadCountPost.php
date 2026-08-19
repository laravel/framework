<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletingLoadCountPost extends Model
{
    protected $table = 'posts';

    use SoftDeletes;

    protected $attributes = [
        'some_default_value' => 100,
    ];

    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(LoadCountComment::class, 'post_id');
    }

    public function likes()
    {
        return $this->hasMany(LoadCountLike::class, 'post_id');
    }
}
