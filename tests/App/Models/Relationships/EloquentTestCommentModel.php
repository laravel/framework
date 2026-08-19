<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentTestCommentModel extends Model
{
    protected $table = 'comments';
    protected $guarded = [];
    public $timestamps = false;
}
