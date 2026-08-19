<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletingMorphToPost extends Model
{
    use SoftDeletes;

    protected $table = 'posts';

    public $timestamps = false;
}
