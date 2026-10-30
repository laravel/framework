<?php

namespace Illuminate\Tests\Database\Fixtures\Models\MorphEagerLoading;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'video_id';
}
