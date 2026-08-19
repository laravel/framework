<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class AfterQueryPost extends Model
{
    protected $table = 'posts';
    protected $guarded = [];
    public $timestamps = false;
}
