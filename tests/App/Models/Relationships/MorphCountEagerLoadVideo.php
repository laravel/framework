<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphCountEagerLoadVideo extends Model
{
    protected $table = 'videos';

    public $timestamps = false;

    public function views()
    {
        return $this->hasMany(MorphCountEagerLoadView::class, 'video_id');
    }
}
