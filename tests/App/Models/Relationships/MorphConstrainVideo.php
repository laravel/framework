<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphConstrainVideo extends Model
{
    protected $table = 'videos';

    public $timestamps = false;
    protected $fillable = ['video_visible'];
    protected $casts = ['video_visible' => 'boolean'];
}
