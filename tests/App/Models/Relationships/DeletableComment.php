<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class DeletableComment extends Model
{
    public $table = 'comments';
    protected $fillable = ['post_id'];
}
