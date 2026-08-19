<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphEagerLoadVideo extends Model
{
    protected $table = 'videos';

    public $timestamps = false;
    protected $primaryKey = 'video_id';
}
