<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphCountEagerLoadView extends Model
{
    protected $table = 'views';

    public $timestamps = false;

    public function video()
    {
        return $this->belongsTo(MorphCountEagerLoadVideo::class);
    }
}
