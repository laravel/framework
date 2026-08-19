<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class CommentX extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'comments';
}
