<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphConstrainPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;
    protected $fillable = ['post_visible'];
    protected $casts = ['post_visible' => 'boolean'];
}
